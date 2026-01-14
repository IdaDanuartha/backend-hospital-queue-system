<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreScheduleRequest;
use App\Http\Requests\Admin\UpdateScheduleRequest;
use App\Models\DoctorSchedule;
use Illuminate\Support\Facades\Cache;

class ScheduleController extends Controller
{
    /**
     * Store doctor schedule
     */
    public function store(StoreScheduleRequest $request)
    {
        $schedule = DoctorSchedule::create($request->validated());

        // Invalidate cache
        Cache::forget('info:doctors:all');

        return response()->json([
            'success' => true,
            'message' => 'Schedule created successfully',
            'data' => $schedule->load('doctor'),
        ], 201);
    }

    /**
     * Update schedule
     */
    public function update(UpdateScheduleRequest $request, string $id)
    {
        $schedule = DoctorSchedule::find($id);

        if (!$schedule) {
            return response()->json([
                'success' => false,
                'message' => 'Schedule not found',
            ], 404);
        }

        $schedule->update($request->validated());

        // Invalidate cache
        Cache::forget('info:doctors:all');

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

        if (!$schedule) {
            return response()->json([
                'success' => false,
                'message' => 'Schedule not found',
            ], 404);
        }
        $schedule->delete();

        // Invalidate cache
        Cache::forget('info:doctors:all');

        return response()->json([
            'success' => true,
            'message' => 'Schedule deleted successfully',
        ]);
    }
}

