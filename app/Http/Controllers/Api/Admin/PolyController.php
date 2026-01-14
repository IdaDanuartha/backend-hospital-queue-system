<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePolyRequest;
use App\Http\Requests\Admin\UpdatePolyRequest;
use App\Models\Poly;
use Illuminate\Support\Facades\Cache;

class PolyController extends Controller
{
    /**
     * Get all polyclinics
     */
    public function index()
    {
        $polys = Cache::remember('admin:polys', 300, function () {
            return Poly::with(['serviceHours', 'doctors'])->latest()->get();
        });

        return response()->json([
            'success' => true,
            'data' => $polys,
        ]);
    }

    /**
     * Store new polyclinic
     */
    public function store(StorePolyRequest $request)
    {
        $poly = Poly::create($request->validated());

        // Invalidate cache
        Cache::forget('admin:polys');
        Cache::forget('info:polys');

        return response()->json([
            'success' => true,
            'message' => 'Polyclinic created successfully',
            'data' => $poly,
        ], 201);
    }

    /**
     * Get polyclinic detail
     */
    public function show(string $id)
    {
        $poly = Poly::with(['serviceHours', 'doctors', 'queueTypes'])->find($id);

        if (!$poly) {
            return response()->json([
                'success' => false,
                'message' => 'Polyclinic not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $poly,
        ]);
    }

    /**
     * Update polyclinic
     */
    public function update(UpdatePolyRequest $request, string $id)
    {
        $poly = Poly::find($id);

        if (!$poly) {
            return response()->json([
                'success' => false,
                'message' => 'Polyclinic not found',
            ], 404);
        }

        $poly->update($request->validated());

        // Invalidate cache
        Cache::forget('admin:polys');
        Cache::forget('info:polys');

        return response()->json([
            'success' => true,
            'message' => 'Polyclinic updated successfully',
            'data' => $poly,
        ]);
    }

    /**
     * Delete polyclinic
     */
    public function destroy(string $id)
    {
        $poly = Poly::find($id);

        if (!$poly) {
            return response()->json([
                'success' => false,
                'message' => 'Polyclinic not found',
            ], 404);
        }

        $poly->delete();

        // Invalidate cache
        Cache::forget('admin:polys');
        Cache::forget('info:polys');

        return response()->json([
            'success' => true,
            'message' => 'Polyclinic deleted successfully',
        ]);
    }
}