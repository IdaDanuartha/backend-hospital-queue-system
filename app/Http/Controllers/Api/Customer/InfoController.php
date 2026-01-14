<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\Poly;
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
        $cacheKey = $polyId ? "info:doctors:poly:{$polyId}" : 'info:doctors:all';

        $doctors = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($polyId) {
            $query = Doctor::with(['poly', 'schedules']);

            if ($polyId) {
                $query->where('poly_id', $polyId);
            }

            return $query->get();
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
}
