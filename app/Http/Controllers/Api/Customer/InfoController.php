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
     * Get all polyclinics with service hours and avg service time
     * 
     * @unauthenticated
     */
    public function getPolys()
    {
        $polys = Poly::active()
            ->with('serviceHours')
            ->get();

        // Add avg_service_time for each poly based on actual service data
        $polys->each(function ($poly) {
            $avgServiceTime = QueueTicket::whereHas('queueType', function ($q) use ($poly) {
                $q->where('poly_id', $poly->id);
            })
                ->whereNotNull('actual_service_minutes')
                ->where('status', \App\Enums\QueueStatus::DONE)
                ->avg('actual_service_minutes');

            $poly->avg_service_time = $avgServiceTime ? round($avgServiceTime, 1) : null;
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
     * Get general statistics
     * 
     * @unauthenticated
     */
    public function getStats()
    {
        $todayTickets = QueueTicket::whereDate('service_date', today())->get();

        $totalCompleted = $todayTickets->where('status', \App\Enums\QueueStatus::DONE)->count();
        $totalWaiting = $todayTickets->where('status', \App\Enums\QueueStatus::WAITING)->count();
        $totalServing = $todayTickets->where('status', \App\Enums\QueueStatus::SERVING)->count();

        // Calculate average service time from actual data
        $avgServiceTime = $todayTickets
            ->where('status', \App\Enums\QueueStatus::DONE)
            ->whereNotNull('actual_service_minutes')
            ->avg('actual_service_minutes');

        return response()->json([
            'success' => true,
            'data' => [
                'date' => today()->toDateString(),
                'total_completed' => $totalCompleted,
                'total_waiting' => $totalWaiting,
                'total_serving' => $totalServing,
                'total_today' => $todayTickets->count(),
                'avg_service_time' => $avgServiceTime ? round($avgServiceTime, 1) : null,
            ],
        ]);
    }
}
