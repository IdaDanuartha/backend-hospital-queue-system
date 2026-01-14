<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\IndexDoctorRequest;
use App\Http\Requests\Admin\StoreDoctorRequest;
use App\Http\Requests\Admin\UpdateDoctorRequest;
use App\Models\Doctor;
use Illuminate\Support\Facades\Cache;

class DoctorController extends Controller
{
    /**
     * Get all doctors
     */
    public function index(IndexDoctorRequest $request)
    {
        $query = Doctor::with(['poly', 'schedules']);

        if ($request->poly_id) {
            $query->where('poly_id', $request->poly_id);
        }

        $doctors = $query->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $doctors,
        ]);
    }

    /**
     * Store new doctor
     */
    public function store(StoreDoctorRequest $request)
    {
        $doctor = Doctor::create($request->validated());

        // Invalidate cache
        Cache::forget('info:doctors:all');

        return response()->json([
            'success' => true,
            'message' => 'Doctor created successfully',
            'data' => $doctor->load('poly'),
        ], 201);
    }

    /**
     * Get doctor detail
     */
    public function show(string $id)
    {
        $doctor = Doctor::with(['poly', 'schedules'])->find($id);

        if (!$doctor) {
            return response()->json([
                'success' => false,
                'message' => 'Doctor not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $doctor,
        ]);
    }

    /**
     * Update doctor
     */
    public function update(UpdateDoctorRequest $request, string $id)
    {
        $doctor = Doctor::find($id);

        if (!$doctor) {
            return response()->json([
                'success' => false,
                'message' => 'Doctor not found',
            ], 404);
        }

        $doctor->update($request->validated());

        // Invalidate cache
        Cache::forget('info:doctors:all');
        Cache::forget("info:doctors:poly:{$doctor->poly_id}");

        return response()->json([
            'success' => true,
            'message' => 'Doctor updated successfully',
            'data' => $doctor->load('poly'),
        ]);
    }

    /**
     * Delete doctor
     */
    public function destroy(string $id)
    {
        $doctor = Doctor::find($id);

        if (!$doctor) {
            return response()->json([
                'success' => false,
                'message' => 'Doctor not found',
            ], 404);
        }

        $polyId = $doctor->poly_id;
        $doctor->delete();

        // Invalidate cache
        Cache::forget('info:doctors:all');
        Cache::forget("info:doctors:poly:{$polyId}");

        return response()->json([
            'success' => true,
            'message' => 'Doctor deleted successfully',
        ]);
    }
}

