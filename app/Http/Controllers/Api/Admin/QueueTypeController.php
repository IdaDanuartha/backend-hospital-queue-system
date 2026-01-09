<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class QueueTypeController extends Controller
{
    /**
     * Get all queue types
     */
    public function index()
    {
        $queueTypes = \App\Models\QueueType::with('poly')->get();

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

        $queueType = \App\Models\QueueType::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Queue type created successfully',
            'data' => $queueType->load('poly'),
        ], 201);
    }

    /**
     * Update queue type
     */
    public function update(Request $request, int $id)
    {
        $queueType = \App\Models\QueueType::findOrFail($id);

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
    public function destroy(int $id)
    {
        $queueType = \App\Models\QueueType::findOrFail($id);
        $queueType->delete();

        return response()->json([
            'success' => true,
            'message' => 'Queue type deleted successfully',
        ]);
    }
}
