<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    /**
     * Get all doctors
     */
    public function index(Request $request)
    {
        $query = \App\Models\Doctor::with(['poly', 'schedules']);

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
     * Store new doctor
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'poly_id' => 'required|exists:polys,id',
            'sip_number' => 'required|string|unique:doctors,sip_number',
            'name' => 'required|string',
            'specialization' => 'nullable|string',
        ]);

        $doctor = \App\Models\Doctor::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Doctor created successfully',
            'data' => $doctor->load('poly'),
        ], 201);
    }

    /**
     * Get doctor detail
     */
    public function show(int $id)
    {
        $doctor = \App\Models\Doctor::with(['poly', 'schedules'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $doctor,
        ]);
    }

    /**
     * Update doctor
     */
    public function update(Request $request, int $id)
    {
        $doctor = \App\Models\Doctor::findOrFail($id);

        $validated = $request->validate([
            'poly_id' => 'required|exists:polys,id',
            'sip_number' => 'required|string|unique:doctors,sip_number,' . $id,
            'name' => 'required|string',
            'specialization' => 'nullable|string',
        ]);

        $doctor->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Doctor updated successfully',
            'data' => $doctor->load('poly'),
        ]);
    }

    /**
     * Delete doctor
     */
    public function destroy(int $id)
    {
        $doctor = \App\Models\Doctor::findOrFail($id);
        $doctor->delete();

        return response()->json([
            'success' => true,
            'message' => 'Doctor deleted successfully',
        ]);
    }
}
