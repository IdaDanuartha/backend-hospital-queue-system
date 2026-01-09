<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\QueueTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Get queue statistics report
     */
    public function queueStatistics(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'poly_id' => 'nullable|exists:polys,id',
            'queue_type_id' => 'nullable|exists:queue_types,id',
        ]);

        $query = QueueTicket::query()
            ->whereBetween('service_date', [$validated['start_date'], $validated['end_date']]);

        if (isset($validated['queue_type_id'])) {
            $query->where('queue_type_id', $validated['queue_type_id']);
        } elseif (isset($validated['poly_id'])) {
            $query->whereHas('queueType', function ($q) use ($validated) {
                $q->where('poly_id', $validated['poly_id']);
            });
        }

        $statistics = $query->selectRaw('
            COUNT(*) as total_queues,
            COUNT(CASE WHEN status = "DONE" THEN 1 END) as completed_queues,
            COUNT(CASE WHEN status = "SKIPPED" THEN 1 END) as skipped_queues,
            AVG(CASE 
                WHEN served_at IS NOT NULL AND issued_at IS NOT NULL 
                THEN TIMESTAMPDIFF(MINUTE, issued_at, served_at) 
            END) as avg_waiting_time_minutes,
            AVG(CASE 
                WHEN finished_at IS NOT NULL AND served_at IS NOT NULL 
                THEN TIMESTAMPDIFF(MINUTE, served_at, finished_at) 
            END) as avg_service_time_minutes
        ')->first();

        return response()->json([
            'success' => true,
            'data' => [
                'period' => [
                    'start_date' => $validated['start_date'],
                    'end_date' => $validated['end_date'],
                ],
                'statistics' => $statistics,
            ],
        ]);
    }

    /**
     * Get busiest polyclinics
     */
    public function busiestPolys(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $busiestPolys = DB::table('queue_tickets')
            ->join('queue_types', 'queue_tickets.queue_type_id', '=', 'queue_types.id')
            ->join('polys', 'queue_types.poly_id', '=', 'polys.id')
            ->whereBetween('queue_tickets.service_date', [$validated['start_date'], $validated['end_date']])
            ->groupBy('polys.id', 'polys.name')
            ->select(
                'polys.id',
                'polys.name',
                DB::raw('COUNT(queue_tickets.id) as total_queues'),
                DB::raw('AVG(TIMESTAMPDIFF(MINUTE, queue_tickets.issued_at, queue_tickets.served_at)) as avg_waiting_time')
            )
            ->orderBy('total_queues', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $busiestPolys,
        ]);
    }

    /**
     * Get busiest hours
     */
    public function busiestHours(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'poly_id' => 'nullable|exists:polys,id',
        ]);

        $query = QueueTicket::query()
            ->whereBetween('service_date', [$validated['start_date'], $validated['end_date']]);

        if (isset($validated['poly_id'])) {
            $query->whereHas('queueType', function ($q) use ($validated) {
                $q->where('poly_id', $validated['poly_id']);
            });
        }

        $busiestHours = $query
            ->selectRaw('HOUR(issued_at) as hour, COUNT(*) as total_queues')
            ->groupBy('hour')
            ->orderBy('total_queues', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'hour' => str_pad($item->hour, 2, '0', STR_PAD_LEFT) . ':00',
                    'total_queues' => $item->total_queues,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $busiestHours,
        ]);
    }

    /**
     * Get daily queue count
     */
    public function dailyQueueCount(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'poly_id' => 'nullable|exists:polys,id',
        ]);

        $query = QueueTicket::query()
            ->whereBetween('service_date', [$validated['start_date'], $validated['end_date']]);

        if (isset($validated['poly_id'])) {
            $query->whereHas('queueType', function ($q) use ($validated) {
                $q->where('poly_id', $validated['poly_id']);
            });
        }

        $dailyCount = $query
            ->selectRaw('service_date, COUNT(*) as total_queues')
            ->groupBy('service_date')
            ->orderBy('service_date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $dailyCount,
        ]);
    }

    /**
     * Get average waiting time trend
     */
    public function waitingTimeTrend(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'poly_id' => 'nullable|exists:polys,id',
        ]);

        $query = QueueTicket::query()
            ->whereBetween('service_date', [$validated['start_date'], $validated['end_date']])
            ->whereNotNull('served_at');

        if (isset($validated['poly_id'])) {
            $query->whereHas('queueType', function ($q) use ($validated) {
                $q->where('poly_id', $validated['poly_id']);
            });
        }

        $trend = $query
            ->selectRaw('
                service_date,
                AVG(TIMESTAMPDIFF(MINUTE, issued_at, served_at)) as avg_waiting_minutes
            ')
            ->groupBy('service_date')
            ->orderBy('service_date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $trend,
        ]);
    }
}
