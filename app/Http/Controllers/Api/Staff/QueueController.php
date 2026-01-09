<?php

namespace App\Http\Controllers\Api\Staff;

use App\Http\Controllers\Controller;
use App\Services\QueueService;
use App\Repositories\Contracts\QueueTicketRepositoryInterface;
use Illuminate\Http\Request;

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
    public function callNext(Request $request)
    {
        $validated = $request->validate([
            'queue_type_id' => 'required|exists:queue_types,id',
        ]);

        try {
            $staff = auth()->user()->staff;
            $ticket = $this->queueService->callNext($validated['queue_type_id'], $staff->id);

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
    public function recall(int $ticketId)
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
    public function skip(int $ticketId, Request $request)
    {
        $validated = $request->validate([
            'remark' => 'nullable|string',
        ]);

        try {
            $staff = auth()->user()->staff;
            $ticket = $this->queueService->skipQueue($ticketId, $staff->id, $validated['remark'] ?? null);

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
    public function startService(int $ticketId)
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
    public function finishService(int $ticketId, Request $request)
    {
        $validated = $request->validate([
            'notes' => 'nullable|string',
        ]);

        try {
            $staff = auth()->user()->staff;
            $ticket = $this->queueService->finishService($ticketId, $staff->id, $validated['notes'] ?? null);

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
}