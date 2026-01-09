<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Poly;
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
            $queueTypes = \App\Models\QueueType::where('poly_id', $poly->id)
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

            foreach ($queueTypes as $type) {
                $queues = \App\Models\QueueTicket::where('queue_type_id', $type->id)
                    ->whereDate('service_date', $date)
                    ->get();

                $polyData['total_today'] += $queues->count();
                $polyData['waiting'] += $queues->where('status', 'WAITING')->count();
                $polyData['serving'] += $queues->where('status', 'SERVING')->count();
                $polyData['done'] += $queues->where('status', 'DONE')->count();
            }

            $dashboard[] = $polyData;
        }

        return response()->json([
            'success' => true,
            'data' => $dashboard,
        ]);
    }
}
