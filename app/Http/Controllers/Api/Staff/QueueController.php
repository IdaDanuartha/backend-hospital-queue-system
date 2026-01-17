<?php

namespace App\Http\Controllers\Api\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\CallNextQueueRequest;
use App\Http\Requests\Staff\FinishServiceRequest;
use App\Http\Requests\Staff\SkipQueueRequest;
use App\Services\QueueService;
use App\Repositories\Contracts\QueueTicketRepositoryInterface;

class QueueController extends Controller
{
    protected $queueService;
    protected $queueTicketRepository;

    public function __construct(
        QueueService $queueService,
        QueueTicketRepositoryInterface $queueTicketRepository
    ) {
        $this->queueService = $queueService;
        $this->queueTicketRepository = $queueTicketRepository;
    }

    /**
     * Get today's queues for staff's poly
     */
    public function getTodayQueues()
    {
        $staff = auth()->user()->staff;

        if (!$staff) {
            return response()->json([
                'success' => false,
                'message' => 'Staff profile not found',
            ], 404);
        }

        $queueTypes = \App\Models\QueueType::where('poly_id', $staff->poly_id)
            ->active()
            ->get();

        $queues = [];
        foreach ($queueTypes as $type) {
            $queues[$type->id] = $this->queueTicketRepository->getTodayQueueByType($type->id);
        }

        return response()->json([
            'success' => true,
            'data' => $queues,
        ]);
    }

    /**
     * Call next queue
     */
    public function callNext(CallNextQueueRequest $request)
    {
        try {
            $staff = auth()->user()->staff;
            $ticket = $this->queueService->callNext($request->queue_type_id, $staff->id);

            return response()->json([
                'success' => true,
                'message' => 'Queue called successfully',
                'data' => $ticket,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Recall current queue
     */
    public function recall(string $ticketId)
    {
        try {
            $staff = auth()->user()->staff;
            $ticket = $this->queueService->recallQueue($ticketId, $staff->id);

            return response()->json([
                'success' => true,
                'message' => 'Queue recalled',
                'data' => $ticket,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Skip queue
     */
    public function skip(string $ticketId, SkipQueueRequest $request)
    {
        try {
            $staff = auth()->user()->staff;
            $ticket = $this->queueService->skipQueue($ticketId, $staff->id, $request->remark);

            return response()->json([
                'success' => true,
                'message' => 'Queue skipped',
                'data' => $ticket,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Start serving queue
     */
    public function startService(string $ticketId)
    {
        try {
            $staff = auth()->user()->staff;
            $ticket = $this->queueService->startService($ticketId, $staff->id);

            return response()->json([
                'success' => true,
                'message' => 'Service started',
                'data' => $ticket,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Finish serving queue
     */
    public function finishService(string $ticketId, FinishServiceRequest $request)
    {
        try {
            $staff = auth()->user()->staff;
            $ticket = $this->queueService->finishService($ticketId, $staff->id, $request->notes);

            return response()->json([
                'success' => true,
                'message' => 'Service finished',
                'data' => $ticket,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get today's skipped queues for staff's poly
     */
    public function getSkippedQueues()
    {
        $staff = auth()->user()->staff;

        if (!$staff) {
            return response()->json([
                'success' => false,
                'message' => 'Staff profile not found',
            ], 404);
        }

        $queueTypes = \App\Models\QueueType::where('poly_id', $staff->poly_id)
            ->active()
            ->get();

        $skippedQueues = [];
        foreach ($queueTypes as $type) {
            $skippedQueues[$type->id] = $this->queueTicketRepository->getSkippedQueues($type->id, today());
        }

        return response()->json([
            'success' => true,
            'data' => $skippedQueues,
        ]);
    }

    /**
     * Recall skipped queue back to waiting queue at the end
     */
    public function recallSkipped(string $ticketId)
    {
        try {
            $staff = auth()->user()->staff;
            $ticket = $this->queueService->recallSkippedQueue($ticketId, $staff->id);

            return response()->json([
                'success' => true,
                'message' => 'Skipped queue recalled to waiting queue',
                'data' => $ticket,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}