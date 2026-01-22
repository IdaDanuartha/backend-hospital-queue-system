<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\QueueStatus;
use App\Http\Controllers\Controller;
use App\Models\Poly;
use App\Models\QueueTicket;
use App\Models\QueueType;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Get admin dashboard data
     */
    public function index(Request $request)
    {
        $date = $request->input('date', today());

        $polys = Poly::active()->get();
        $dashboard = [];

        foreach ($polys as $poly) {
            $queueTypes = QueueType::where('poly_id', $poly->id)
                ->active()
                ->get();

            $polyData = [
                'poly' => $poly,
                'total_today' => 0,
                'waiting' => 0,
                'serving' => 0,
                'done' => 0,
                'avg_waiting_time' => 0,
            ];

            $allQueues = collect();

            foreach ($queueTypes as $type) {
                $queues = QueueTicket::where('queue_type_id', $type->id)
                    ->whereDate('service_date', $date)
                    ->get();

                $allQueues = $allQueues->merge($queues);

                $polyData['total_today'] += $queues->count();
                $polyData['waiting'] += $queues->where('status', QueueStatus::WAITING)->count();
                $polyData['serving'] += $queues->where('status', QueueStatus::SERVING)->count();
                $polyData['done'] += $queues->where('status', QueueStatus::DONE)->count();
            }

            // Calculate avg_waiting_time for served/done tickets
            $polyData['avg_waiting_time'] = $allQueues->where('status', '!=', QueueStatus::WAITING)
                ->avg(function ($ticket) {
                    return $ticket->getWaitingTimeMinutes();
                });

            $dashboard[] = $polyData;
        }

        return response()->json([
            'success' => true,
            'data' => $dashboard,
        ]);
    }
}
