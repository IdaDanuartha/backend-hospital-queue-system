<?php

namespace App\Services;

use App\Enums\QueueStatus;
use App\Models\QueueEvent;
use App\Repositories\Contracts\QueueTicketRepositoryInterface;
use Illuminate\Support\Facades\DB;

class QueueService
{
    protected $queueTicketRepository;

    public function __construct(QueueTicketRepositoryInterface $queueTicketRepository)
    {
        $this->queueTicketRepository = $queueTicketRepository;
    }

    public function takeQueue($queueTypeId, $patientName, $phoneNumber, $latitude = null, $longitude = null)
    {
        // Validate geofencing if enabled
        if ($this->isGeofencingEnabled()) {
            if (!$latitude || !$longitude) {
                throw new \Exception('Lokasi diperlukan untuk mengambil antrian');
            }

            $maxDistance = (int) \App\Models\SystemSetting::get('MAX_DISTANCE_METER', 100);
            if (!$this->isWithinHospitalRadius($latitude, $longitude)) {
                throw new \Exception("Anda harus berada dalam radius {$maxDistance} meter dari rumah sakit untuk mengambil antrian");
            }
        }

        return DB::transaction(function () use ($queueTypeId, $patientName, $phoneNumber) {
            $serviceDate = today();

            // Check if this phone number already has an active ticket for this queue type today
            // Only block if status is not DONE or CANCELLED
            $existingTicket = \App\Models\QueueTicket::where('queue_type_id', $queueTypeId)
                ->whereDate('service_date', $serviceDate)
                ->where('phone_number', $phoneNumber)
                ->whereNotIn('status', [QueueStatus::DONE, QueueStatus::CANCELLED])
                ->first();

            if ($existingTicket) {
                throw new \Exception('Nomor telepon ini sudah mengambil antrian jenis ini hari ini dan masih dalam proses');
            }

            $nextNumber = $this->queueTicketRepository->getNextQueueNumber($queueTypeId, $serviceDate);

            $queueType = \App\Models\QueueType::findOrFail($queueTypeId);

            if (!$queueType->is_active) {
                throw new \Exception('Queue type is currently inactive');
            }

            $displayNumber = $queueType->code_prefix . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

            // Auto-assign doctor based on load balancing
            $poly = $queueType->poly;
            $today = now()->dayOfWeekIso;

            // Get doctors who have schedule today for this poly
            $availableDoctors = \App\Models\Doctor::where('poly_id', $poly->id)
                ->whereHas('schedules', function ($query) use ($today) {
                    $query->where('day_of_week', $today);
                })
                ->withCount([
                    'assignedTickets' => function ($query) use ($serviceDate) {
                        $query->where('service_date', $serviceDate)
                            ->where('status', 'WAITING');
                    }
                ])
                ->get();

            $assignedDoctorId = null;
            if ($availableDoctors->isNotEmpty()) {
                // Find doctor with minimum waiting tickets
                $assignedDoctorId = $availableDoctors->sortBy('assigned_tickets_count')->first()->id;
            }

            $ticket = $this->queueTicketRepository->create([
                'queue_type_id' => $queueTypeId,
                'assigned_doctor_id' => $assignedDoctorId,
                'service_date' => $serviceDate,
                'queue_number' => $nextNumber,
                'display_number' => $displayNumber,
                'patient_name' => $patientName,
                'phone_number' => $phoneNumber,
                'issued_at' => now(),
                'status' => 'WAITING',
            ]);

            // Generate public token
            $token = \App\Models\PublicQueueToken::generate($ticket->id);

            return [
                'ticket' => $ticket->load('queueType'),
                'token' => $token->token,
            ];
        });
    }

    public function callNext($queueTypeId, $staffId)
    {
        return DB::transaction(function () use ($queueTypeId, $staffId) {
            $serviceDate = today();
            $nextQueue = $this->queueTicketRepository->getWaitingQueues($queueTypeId, $serviceDate)->first();

            if (!$nextQueue) {
                throw new \Exception('No waiting queue available');
            }

            $ticket = $this->queueTicketRepository->updateStatus($nextQueue->id, 'CALLED', $staffId);

            // Track called time
            $ticket->update(['called_at' => now()]);

            $this->logQueueEvent($ticket->id, $staffId, 'CALL_NEXT', 'WAITING', 'CALLED');

            return $ticket->load(['queueType', 'handledByStaff.user']);
        });
    }

    public function skipQueue($ticketId, $staffId, $remark = null)
    {
        return DB::transaction(function () use ($ticketId, $staffId, $remark) {
            $previousStatus = $this->queueTicketRepository->find($ticketId)->status;
            $ticket = $this->queueTicketRepository->updateStatus($ticketId, 'SKIPPED', $staffId);

            $this->logQueueEvent($ticketId, $staffId, 'SKIP', $previousStatus, 'SKIPPED', $remark);

            return $ticket;
        });
    }

    public function recallQueue($ticketId, $staffId)
    {
        return DB::transaction(function () use ($ticketId, $staffId) {
            $ticket = $this->queueTicketRepository->find($ticketId);

            if (!in_array($ticket->status, [QueueStatus::CALLED->value, 'SKIPPED'])) {
                throw new \Exception('Only called or skipped queue can be recalled');
            }

            $this->logQueueEvent($ticketId, $staffId, 'RECALL', 'CALLED', 'CALLED');

            return $ticket->load(['queueType', 'handledByStaff.user']);
        });
    }

    public function startService($ticketId, $staffId)
    {
        return DB::transaction(function () use ($ticketId, $staffId) {
            $ticket = $this->queueTicketRepository->updateStatus($ticketId, 'SERVING', $staffId);

            // Track service start time
            $ticket->update(['service_started_at' => now()]);

            $this->logQueueEvent($ticketId, $staffId, 'START_SERVICE', 'CALLED', 'SERVING');

            return $ticket;
        });
    }

    public function finishService($ticketId, $staffId, $notes = null)
    {
        return DB::transaction(function () use ($ticketId, $staffId, $notes) {
            $ticket = $this->queueTicketRepository->find($ticketId);

            // Calculate actual service time
            $actualMinutes = null;
            if ($ticket->service_started_at) {
                $actualMinutes = (int) abs(now()->diffInMinutes($ticket->service_started_at));
            }

            $ticket->update([
                'status' => 'DONE',
                'finished_at' => now(),
                'notes' => $notes,
                'actual_service_minutes' => $actualMinutes,
            ]);

            $this->logQueueEvent($ticketId, $staffId, 'FINISH', 'SERVING', 'DONE');

            return $ticket;
        });
    }

    public function recallSkippedQueue($ticketId, $staffId)
    {
        return DB::transaction(function () use ($ticketId, $staffId) {
            $ticket = $this->queueTicketRepository->find($ticketId);

            if ($ticket->status !== QueueStatus::SKIPPED) {
                throw new \Exception('Hanya antrian yang berstatus skipped yang dapat di-recall');
            }

            // Get the max queue number for today's waiting queue
            $maxQueueNumber = $this->queueTicketRepository->getMaxQueueNumber(
                $ticket->queue_type_id,
                $ticket->service_date
            );

            // Update the queue number to be at the end and change status to WAITING
            $previousStatus = $ticket->status;
            $ticket->update([
                'queue_number' => $maxQueueNumber + 1,
                'status' => 'WAITING',
                'display_number' => $ticket->queueType->code_prefix . '-' . str_pad($maxQueueNumber + 1, 3, '0', STR_PAD_LEFT),
            ]);

            $this->logQueueEvent($ticketId, $staffId, 'RECALL', $previousStatus, 'WAITING', 'Recalled from skipped queue to end of waiting queue');

            return $ticket->load(['queueType', 'handledByStaff.user']);
        });
    }

    public function cancelQueue($token)
    {
        return DB::transaction(function () use ($token) {
            $publicToken = \App\Models\PublicQueueToken::where('token', $token)->firstOrFail();

            if ($publicToken->isExpired()) {
                throw new \Exception('Token telah kedaluwarsa');
            }

            $ticket = $publicToken->queueTicket;

            if ($ticket->status !== QueueStatus::WAITING) {
                throw new \Exception('Hanya antrian yang berstatus menunggu yang dapat dibatalkan');
            }

            $previousStatus = $ticket->status;
            $ticket->update([
                'status' => 'CANCELLED',
                'cancelled_at' => now(),
            ]);

            $this->logQueueEvent($ticket->id, null, 'CANCEL', $previousStatus, 'CANCELLED', 'Cancelled by patient');

            return $ticket->load('queueType');
        });
    }

    public function getQueueStatus($token)
    {
        $publicToken = \App\Models\PublicQueueToken::where('token', $token)->firstOrFail();

        if ($publicToken->isExpired()) {
            throw new \Exception('Token has expired');
        }

        $ticket = $publicToken->queueTicket()->with(['queueType.poly'])->first();

        // Get current queue
        $currentQueue = $this->queueTicketRepository->getCurrentQueue(
            $ticket->queue_type_id,
            $ticket->service_date
        );

        $currentQueueNumber = $currentQueue?->queue_number ?? 0;

        // Count only WAITING queues ahead of this ticket
        $queuesAhead = \App\Models\QueueTicket::where('queue_type_id', $ticket->queue_type_id)
            ->whereDate('service_date', $ticket->service_date)
            ->where('status', \App\Enums\QueueStatus::WAITING)
            ->where('queue_number', '<', $ticket->queue_number)
            ->count();

        // === AI PREDICTION ===
        $predictor = app(\App\Services\AI\QueueWaitTimePredictor::class);
        $prediction = $predictor->predict(
            $ticket->queue_type_id,
            $currentQueueNumber,
            $ticket->queue_number,
            $ticket->service_date
        );

        return [
            'token' => $publicToken->token,
            'ticket' => $ticket,
            'current_queue' => $currentQueue,
            'queues_ahead' => $queuesAhead,
            'ai_prediction' => $prediction,
            // Legacy field for backward compatibility
            'estimated_waiting_minutes' => $prediction['estimated_minutes'],
        ];
    }

    protected function logQueueEvent($ticketId, $staffId, $action, $previousStatus, $newStatus, $remark = null)
    {
        QueueEvent::create([
            'queue_ticket_id' => $ticketId,
            'staff_id' => $staffId,
            'action' => $action,
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
            'event_time' => now(),
            'remark' => $remark,
        ]);
    }

    protected function isGeofencingEnabled()
    {
        return \App\Models\SystemSetting::get('GEOFENCE_ENABLED', false) === 'true';
    }

    protected function isWithinHospitalRadius($latitude, $longitude)
    {
        $hospitalLat = (float) \App\Models\SystemSetting::get('HOSPITAL_LAT', 0);
        $hospitalLng = (float) \App\Models\SystemSetting::get('HOSPITAL_LNG', 0);
        $maxDistance = (int) \App\Models\SystemSetting::get('MAX_DISTANCE_METER', 100);

        $distance = $this->calculateDistance($latitude, $longitude, $hospitalLat, $hospitalLng);

        return $distance <= $maxDistance;
    }

    protected function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371000; // meters

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    protected function calculateEstimatedWaitingTime($queueTypeId, $remainingQueues)
    {
        $queueType = \App\Models\QueueType::find($queueTypeId);

        if (!$queueType)
            return 0;

        return $remainingQueues * $queueType->avg_service_minutes;
    }
}