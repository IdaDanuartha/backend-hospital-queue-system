<?php

namespace App\Http\Controllers\Api\Staff;

use App\Enums\QueueStatus;
use App\Http\Controllers\Controller;
use App\Models\QueueTicket;
use App\Models\QueueType;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Get staff dashboard data
     */
    public function index()
    {
        $staff = auth()->user()->staff;

        if (!$staff) {
            return response()->json([
                'success' => false,
                'message' => 'Staff profile not found',
            ], 404);
        }

        $queueTypes = QueueType::where('poly_id', $staff->poly_id)
            ->active()
            ->get();

        $dashboard = [];

        foreach ($queueTypes as $type) {
            $todayQueues = QueueTicket::where('queue_type_id', $type->id)
                ->whereDate('service_date', today())
                ->get();

            // Calculate average service time from actual data (DONE tickets only)
            $avgServiceTime = $todayQueues
                ->where('status', QueueStatus::DONE)
                ->whereNotNull('actual_service_minutes')
                ->avg('actual_service_minutes');

            $dashboard[] = [
                'queue_type' => $type,
                'total_today' => $todayQueues->count(),
                'waiting' => $todayQueues->where('status', QueueStatus::WAITING)->count(),
                'serving' => $todayQueues->where('status', QueueStatus::SERVING)->count(),
                'done' => $todayQueues->where('status', QueueStatus::DONE)->count(),
                'avg_service_time' => $avgServiceTime ? round($avgServiceTime, 1) : null,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'staff' => $staff->load('poly'),
                'dashboard' => $dashboard,
            ],
        ]);
    }
}
