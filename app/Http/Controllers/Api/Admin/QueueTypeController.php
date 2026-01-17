<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreQueueTypeRequest;
use App\Http\Requests\Admin\UpdateQueueTypeRequest;
use App\Models\QueueType;
use Illuminate\Support\Facades\Cache;

class QueueTypeController extends Controller
{
    /**
     * Get all queue types
     */
    public function index()
    {
        $queueTypes = QueueType::with('poly')->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $queueTypes,
        ]);
    }

    /**
     * Store new queue type
     */
    public function store(StoreQueueTypeRequest $request)
    {
        $queueType = QueueType::create($request->validated());

        // Invalidate cache
        Cache::forget('info:queue_types');

        return response()->json([
            'success' => true,
            'message' => 'Queue type created successfully',
            'data' => $queueType->load('poly'),
        ], 201);
    }

    /**
     * Get queue type detail
     */
    public function show(string $id)
    {
        $queueType = QueueType::with('poly')->find($id);

        if (!$queueType) {
            return response()->json([
                'success' => false,
                'message' => 'Queue type not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $queueType,
        ]);
    }

    /**
     * Update queue type
     */
    public function update(UpdateQueueTypeRequest $request, string $id)
    {
        $queueType = QueueType::find($id);

        if (!$queueType) {
            return response()->json([
                'success' => false,
                'message' => 'Queue type not found',
            ], 404);
        }

        $queueType->update($request->validated());

        // Invalidate cache
        Cache::forget('info:queue_types');

        return response()->json([
            'success' => true,
            'message' => 'Queue type updated successfully',
            'data' => $queueType->load('poly'),
        ]);
    }

    /**
     * Delete queue type
     */
    public function destroy(string $id)
    {
        $queueType = QueueType::find($id);

        if (!$queueType) {
            return response()->json([
                'success' => false,
                'message' => 'Queue type not found',
            ], 404);
        }
        $queueType->delete();

        // Invalidate cache
        Cache::forget('info:queue_types');

        return response()->json([
            'success' => true,
            'message' => 'Queue type deleted successfully',
        ]);
    }
}

