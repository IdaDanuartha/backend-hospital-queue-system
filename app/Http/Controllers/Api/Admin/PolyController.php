<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Poly;
use Illuminate\Http\Request;

class PolyController extends Controller
{
    /**
     * Get all polyclinics
     */
    public function index()
    {
        $polys = Poly::with(['serviceHours', 'doctors'])->get();

        return response()->json([
            'success' => true,
            'data' => $polys,
        ]);
    }

    /**
     * Store new polyclinic
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:polys,code',
            'name' => 'required|string',
            'location' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $poly = Poly::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Polyclinic created successfully',
            'data' => $poly,
        ], 201);
    }

    /**
     * Get polyclinic detail
     */
    public function show(int $id)
    {
        $poly = Poly::with(['serviceHours', 'doctors', 'queueTypes'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $poly,
        ]);
    }

    /**
     * Update polyclinic
     */
    public function update(Request $request, int $id)
    {
        $poly = Poly::findOrFail($id);

        $validated = $request->validate([
            'code' => 'required|string|unique:polys,code,' . $id,
            'name' => 'required|string',
            'location' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $poly->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Polyclinic updated successfully',
            'data' => $poly,
        ]);
    }

    /**
     * Delete polyclinic
     */
    public function destroy(int $id)
    {
        $poly = Poly::findOrFail($id);
        $poly->delete();

        return response()->json([
            'success' => true,
            'message' => 'Polyclinic deleted successfully',
        ]);
    }
}