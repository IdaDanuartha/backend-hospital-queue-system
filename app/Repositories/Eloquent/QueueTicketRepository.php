<?php

namespace App\Repositories\Eloquent;

use App\Models\QueueTicket;
use App\Repositories\Contracts\QueueTicketRepositoryInterface;
use Illuminate\Support\Facades\DB;

class QueueTicketRepository extends BaseRepository implements QueueTicketRepositoryInterface
{
    public function __construct(QueueTicket $model)
    {
        $this->model = $model;
    }

    public function getTodayQueueByType($queueTypeId)
    {
        return $this->model
            ->where('queue_type_id', $queueTypeId)
            ->whereDate('service_date', today())
            ->with(['queueType', 'handledByStaff.user'])
            ->orderBy('queue_number')
            ->get();
    }

    public function getNextQueueNumber($queueTypeId, $serviceDate)
    {
        return DB::transaction(function () use ($queueTypeId, $serviceDate) {
            // PostgreSQL doesn't support FOR UPDATE with aggregate functions
            // So we get the max without lock first, then verify with a lock
            $lastQueue = $this->model
                ->where('queue_type_id', $queueTypeId)
                ->where('service_date', $serviceDate)
                ->orderBy('queue_number', 'desc')
                ->lockForUpdate()
                ->first();

            return ($lastQueue?->queue_number ?? 0) + 1;
        });
    }

    public function getCurrentQueue($queueTypeId, $serviceDate)
    {
        return $this->model
            ->where('queue_type_id', $queueTypeId)
            ->where('service_date', $serviceDate)
            ->whereIn('status', ['CALLED', 'SERVING'])
            ->with(['queueType', 'handledByStaff.user'])
            ->latest('called_at')
            ->first();
    }

    public function getWaitingQueues($queueTypeId, $serviceDate)
    {
        return $this->model
            ->where('queue_type_id', $queueTypeId)
            ->where('service_date', $serviceDate)
            ->where('status', 'WAITING')
            ->with(['queueType'])
            ->orderBy('queue_number')
            ->get();
    }

    public function getSkippedQueues($queueTypeId, $serviceDate)
    {
        return $this->model
            ->where('queue_type_id', $queueTypeId)
            ->where('service_date', $serviceDate)
            ->where('status', 'SKIPPED')
            ->with(['queueType', 'handledByStaff.user'])
            ->orderBy('queue_number')
            ->get();
    }

    public function getMaxQueueNumber($queueTypeId, $serviceDate)
    {
        return $this->model
            ->where('queue_type_id', $queueTypeId)
            ->where('service_date', $serviceDate)
            ->where('status', 'WAITING')
            ->max('queue_number') ?? 0;
    }

    public function updateStatus($id, $status, $staffId = null)
    {
        $ticket = $this->find($id);

        $updates = ['status' => $status];

        if ($status === 'CALLED') {
            $updates['called_at'] = now();
        } elseif ($status === 'SERVING') {
            $updates['served_at'] = now();
        } elseif ($status === 'DONE') {
            $updates['finished_at'] = now();
        }

        if ($staffId) {
            $updates['handled_by_staff_id'] = $staffId;
        }

        $ticket->update($updates);
        return $ticket;
    }

    public function getQueueStatistics($queueTypeId, $startDate, $endDate)
    {
        return $this->model
            ->where('queue_type_id', $queueTypeId)
            ->whereBetween('service_date', [$startDate, $endDate])
            ->selectRaw('
                COUNT(*) as total_queues,
                AVG(EXTRACT(EPOCH FROM (served_at - issued_at)) / 60) as avg_waiting_time,
                AVG(EXTRACT(EPOCH FROM (finished_at - served_at)) / 60) as avg_service_time
            ')
            ->first();
    }
}
