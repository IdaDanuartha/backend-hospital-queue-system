<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\TakeQueueRequest;
use App\Services\QueueService;
use Illuminate\Http\Request;

class QueueController extends Controller
{
    protected $queueService;

    public function __construct(QueueService $queueService)
    {
        $this->queueService = $queueService;
    }

    /**
     * Take a new queue number
     * 
     * @unauthenticated
     */
    public function takeQueue(TakeQueueRequest $request)
    {
        $validated = $request->validated();

        try {
            $result = $this->queueService->takeQueue(
                $validated['queue_type_id'],
                $validated['patient_name'] ?? null,
                $validated['phone_number'],
                $validated['latitude'] ?? null,
                $validated['longitude'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Queue taken successfully',
                'data' => $result,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get queue status by token
     * 
     * @unauthenticated
     */
    public function getStatus(string $token)
    {
        try {
            $result = $this->queueService->getQueueStatus($token);

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Cancel queue by token
     * 
     * @unauthenticated
     */
    public function cancelQueue(string $token)
    {
        try {
            $ticket = $this->queueService->cancelQueue($token);

            return response()->json([
                'success' => true,
                'message' => 'Antrian berhasil dibatalkan',
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
