<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDoctorRequest;
use App\Models\Doctor;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    /**
     * Get all doctors
     */
    public function index(Request $request)
    {
        $request->validate([
            /**
             * Filter berdasarkan ID poliklinik
             * @query
             * @example 9d4e8f12-3456-7890-abcd-ef1234567890
             */
            'poly_id' => 'nullable|uuid|exists:polys,id',
        ]);

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
     * Store new doctor
     */
    public function store(StoreDoctorRequest $request)
    {
        $doctor = Doctor::create($request->validated());

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
        $doctor = Doctor::with(['poly', 'schedules'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $doctor,
        ]);
    }

    /**
     * Update doctor
     */
    public function update(Request $request, string $id)
    {
        $doctor = Doctor::findOrFail($id);

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
    public function destroy(string $id)
    {
        $doctor = Doctor::findOrFail($id);
        $doctor->delete();

        return response()->json([
            'success' => true,
            'message' => 'Doctor deleted successfully',
        ]);
    }
}
