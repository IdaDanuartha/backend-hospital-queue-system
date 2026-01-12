<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\QueueType;
use Illuminate\Http\Request;

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
    public function store(Request $request)
    {
        $validated = $request->validate([
            'poly_id' => 'nullable|exists:polys,id',
            'name' => 'required|string',
            'code_prefix' => 'required|string|max:5',
            'service_unit' => 'nullable|string',
            'avg_service_minutes' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
        ]);

        $queueType = QueueType::create($validated);

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

        if(!$queueType) {
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
    public function update(Request $request, string $id)
    {
        $queueType = QueueType::find($id);

        if(!$queueType) {
            return response()->json([
                'success'=> false,
                'message'=> 'Queue type not found',
            ], 404);
        }

        $validated = $request->validate([
            'poly_id' => 'nullable|exists:polys,id',
            'name' => 'required|string',
            'code_prefix' => 'required|string|max:5',
            'service_unit' => 'nullable|string',
            'avg_service_minutes' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
        ]);

        $queueType->update($validated);

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

        if(!$queueType) {
            return response()->json([
                'success'=> false,
                'message'=> 'Queue type not found',
            ], 404);
        }
        $queueType->delete();

        return response()->json([
            'success' => true,
            'message' => 'Queue type deleted successfully',
        ]);
    }
}
