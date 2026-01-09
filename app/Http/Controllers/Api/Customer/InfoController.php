<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\Poly;
use App\Models\QueueType;
use Illuminate\Http\Request;

class InfoController extends Controller
{
    /**
     * Get all polyclinics with service hours
     * 
     * @unauthenticated
     */
    public function getPolys()
    {
        $polys = Poly::active()
            ->with('serviceHours')
            ->get();

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
        $query = Doctor::with(['poly', 'schedules']);

        if ($request->poly_id) {
            $query->where('poly_id', $request->poly_id);
        }

        $doctors = $query->get();

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
        $queueTypes = QueueType::active()
            ->with('poly')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $queueTypes,
        ]);
    }
}
