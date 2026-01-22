<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\Poly;
use App\Models\QueueTicket;
use App\Models\QueueType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class InfoController extends Controller
{
    /**
     * Cache TTL in seconds (5 minutes)
     */
    private const CACHE_TTL = 300;

    /**
     * Get all polyclinics with service hours
     * 
     * @unauthenticated
     */
    public function getPolys()
    {
        $polys = Cache::remember('info:polys', self::CACHE_TTL, function () {
            return Poly::active()
                ->with('serviceHours')
                ->get();
        });

        return response()->json([
            'success' => true,
            'data' => $polys,
        ]);
    }

    /**
     * Get doctor schedules
     * 
     * @unauthenticated
     */
    public function getDoctorSchedules(Request $request)
    {
        $polyId = $request->poly_id;
        $todayDayOfWeek = now()->dayOfWeekIso; // 1 = Monday, 7 = Sunday

        $query = Doctor::with(['poly', 'schedules']);

        if ($polyId) {
            $query->where('poly_id', $polyId);
        }

        $doctors = $query->get();

        // Add remaining_quota for each schedule
        $doctors->each(function ($doctor) use ($todayDayOfWeek) {
            // Get today's ticket count for this doctor's poly
            $todayTicketCount = QueueTicket::whereHas('queueType', function ($q) use ($doctor) {
                $q->where('poly_id', $doctor->poly_id);
            })
                ->whereDate('service_date', today())
                ->count();

            $doctor->schedules->each(function ($schedule) use ($todayDayOfWeek, $todayTicketCount) {
                // Only calculate remaining_quota for today's schedule
                if ($schedule->day_of_week->value === $todayDayOfWeek) {
                    $schedule->remaining_quota = max(0, $schedule->max_quota - $todayTicketCount);
                } else {
                    $schedule->remaining_quota = $schedule->max_quota;
                }
            });
        });

        return response()->json([
            'success' => true,
            'data' => $doctors,
        ]);
    }

    /**
     * Get available queue types
     * 
     * @unauthenticated
     */
    public function getQueueTypes()
    {
        $queueTypes = Cache::remember('info:queue_types', self::CACHE_TTL, function () {
            return QueueType::active()
                ->with('poly')
                ->get();
        });

        return response()->json([
            'success' => true,
            'data' => $queueTypes,
        ]);
    }

    /**
     * Get total completed patients for today
     * 
     * @unauthenticated
     */
    public function getTotalCompletedPatients()
    {
        $totalCompleted = QueueTicket::whereDate('service_date', today())
            ->where('status', \App\Enums\QueueStatus::DONE)
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_completed' => $totalCompleted,
                'date' => today()->toDateString(),
            ],
        ]);
    }
}
