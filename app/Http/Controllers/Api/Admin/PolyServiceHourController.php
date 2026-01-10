<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PolyServiceHourController extends Controller
{
    /**
     * Store poly service hour
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'poly_id' => 'required|exists:polys,id',
            'day_of_week' => 'required|integer|min:0|max:6',
            'open_time' => 'required|date_format:H:i',
            'close_time' => 'required|date_format:H:i|after:open_time',
            'is_active' => 'boolean',
        ]);

        $serviceHour = \App\Models\PolyServiceHour::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Service hour created successfully',
            'data' => $serviceHour->load('poly'),
        ], 201);
    }

    /**
     * Update service hour
     */
    public function update(Request $request, string $id)
    {
        $serviceHour = \App\Models\PolyServiceHour::findOrFail($id);

        $validated = $request->validate([
            'day_of_week' => 'required|integer|min:0|max:6',
            'open_time' => 'required|date_format:H:i',
            'close_time' => 'required|date_format:H:i|after:open_time',
            'is_active' => 'boolean',
        ]);

        $serviceHour->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Service hour updated successfully',
            'data' => $serviceHour,
        ]);
    }

    /**
     * Delete service hour
     */
    public function destroy(string $id)
    {
        $serviceHour = \App\Models\PolyServiceHour::findOrFail($id);
        $serviceHour->delete();

        return response()->json([
            'success' => true,
            'message' => 'Service hour deleted successfully',
        ]);
    }
}
