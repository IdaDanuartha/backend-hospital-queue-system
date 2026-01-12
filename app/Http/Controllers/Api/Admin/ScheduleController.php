<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\DoctorSchedule;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    /**
     * Store doctor schedule
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'doctor_id' => 'required|exists:doctors,id',
            'day_of_week' => 'required|integer|min:0|max:6',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'max_quota' => 'nullable|integer|min:1',
        ]);

        $schedule = DoctorSchedule::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Schedule created successfully',
            'data' => $schedule->load('doctor'),
        ], 201);
    }

    /**
     * Update schedule
     */
    public function update(Request $request, string $id)
    {
        $schedule = DoctorSchedule::find($id);

        if(!$schedule) {
            return response()->json([
                'success' => false,
                'message' => 'Schedule not found',
            ], 404);
        }

        $validated = $request->validate([
            'day_of_week' => 'required|integer|min:0|max:6',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'max_quota' => 'nullable|integer|min:1',
        ]);

        $schedule->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Schedule updated successfully',
            'data' => $schedule,
        ]);
    }

    /**
     * Delete schedule
     */
    public function destroy(string $id)
    {
        $schedule = DoctorSchedule::find($id);

        if(!$schedule) {
            return response()->json([
                'success' => false,
                'message' => 'Schedule not found',
            ], 404);
        }
        $schedule->delete();

        return response()->json([
            'success' => true,
            'message' => 'Schedule deleted successfully',
        ]);
    }
}
