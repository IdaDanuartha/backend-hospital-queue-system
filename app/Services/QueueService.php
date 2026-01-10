<?php

namespace App\Services;

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

    public function takeQueue($queueTypeId, $latitude = null, $longitude = null)
    {
        // Validate geofencing if enabled
        if ($this->isGeofencingEnabled()) {
            if (!$latitude || !$longitude) {
                throw new \Exception('Location is required');
            }

            if (!$this->isWithinHospitalRadius($latitude, $longitude)) {
                throw new \Exception('You must be within hospital area to take a queue');
            }
        }

        return DB::transaction(function () use ($queueTypeId) {
            $serviceDate = today();
            $nextNumber = $this->queueTicketRepository->getNextQueueNumber($queueTypeId, $serviceDate);

            $queueType = \App\Models\QueueType::findOrFail($queueTypeId);

            if (!$queueType->is_active) {
                throw new \Exception('Queue type is currently inactive');
            }

            $displayNumber = $queueType->code_prefix . '-' . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

            $ticket = $this->queueTicketRepository->create([
                'queue_type_id' => $queueTypeId,
                'service_date' => $serviceDate,
                'queue_number' => $nextNumber,
                'display_number' => $displayNumber,
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

            // Get next waiting queue
            $nextQueue = $this->queueTicketRepository->getWaitingQueues($queueTypeId, $serviceDate)->first();

            if (!$nextQueue) {
                throw new \Exception('No waiting queue available');
            }

            // Update status to CALLED
            $ticket = $this->queueTicketRepository->updateStatus($nextQueue->id, 'CALLED', $staffId);

            // Log event
            $this->logQueueEvent($ticket->id, $staffId, 'CALL_NEXT', 'WAITING', 'CALLED');

            return $ticket->load(['queueType', 'handledByStaff.user']);
        });
    }

    public function skipQueue($ticketId, $staffId, $remark = null)
    {
        return DB::transaction(function () use ($ticketId, $staffId, $remark) {
            $ticket = $this->queueTicketRepository->updateStatus($ticketId, 'SKIPPED', $staffId);

            $this->logQueueEvent($ticketId, $staffId, 'SKIP', $ticket->status, 'SKIPPED', $remark);

            return $ticket;
        });
    }

    public function recallQueue($ticketId, $staffId)
    {
        return DB::transaction(function () use ($ticketId, $staffId) {
            $ticket = $this->queueTicketRepository->find($ticketId);

            if ($ticket->status !== 'CALLED') {
                throw new \Exception('Only called queue can be recalled');
            }

            $this->logQueueEvent($ticketId, $staffId, 'RECALL', 'CALLED', 'CALLED');

            return $ticket->load(['queueType', 'handledByStaff.user']);
        });
    }

    public function startService($ticketId, $staffId)
    {
        return DB::transaction(function () use ($ticketId, $staffId) {
            $ticket = $this->queueTicketRepository->updateStatus($ticketId, 'SERVING', $staffId);

            $this->logQueueEvent($ticketId, $staffId, 'START_SERVICE', 'CALLED', 'SERVING');

            return $ticket;
        });
    }

    public function finishService($ticketId, $staffId, $notes = null)
    {
        return DB::transaction(function () use ($ticketId, $staffId, $notes) {
            $ticket = $this->queueTicketRepository->find($ticketId);
            $ticket->update([
                'status' => 'DONE',
                'finished_at' => now(),
                'notes' => $notes,
            ]);

            $this->logQueueEvent($ticketId, $staffId, 'FINISH', 'SERVING', 'DONE');

            return $ticket;
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

        // Get remaining queues
        $remainingQueues = $this->queueTicketRepository->getWaitingQueues(
            $ticket->queue_type_id,
            $ticket->service_date
        )->where('queue_number', '<', $ticket->queue_number)->count();

        // Calculate estimated waiting time
        $estimatedWaitingTime = $this->calculateEstimatedWaitingTime(
            $ticket->queue_type_id,
            $remainingQueues
        );

        return [
            'ticket' => $ticket,
            'current_queue' => $currentQueue,
            'remaining_queues' => $remainingQueues,
            'estimated_waiting_minutes' => $estimatedWaitingTime,
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