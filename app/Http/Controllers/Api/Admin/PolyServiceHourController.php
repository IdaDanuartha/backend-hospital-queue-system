<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePolyServiceHourRequest;
use App\Http\Requests\Admin\UpdatePolyServiceHourRequest;
use App\Models\PolyServiceHour;
use Illuminate\Support\Facades\Cache;

class PolyServiceHourController extends Controller
{
    /**
     * Store poly service hour
     */
    public function store(StorePolyServiceHourRequest $request)
    {
        $serviceHour = PolyServiceHour::create($request->validated());

        // Invalidate cache
        Cache::forget('info:polys');
        Cache::forget('admin:polys');

        return response()->json([
            'success' => true,
            'message' => 'Service hour created successfully',
            'data' => $serviceHour->load('poly'),
        ], 201);
    }

    /**
     * Update service hour
     */
    public function update(UpdatePolyServiceHourRequest $request, string $id)
    {
        $serviceHour = PolyServiceHour::find($id);

        if (!$serviceHour) {
            return response()->json([
                'success' => false,
                'message' => 'Service hour not found',
            ], 404);
        }

        $serviceHour->update($request->validated());

        // Invalidate cache
        Cache::forget('info:polys');
        Cache::forget('admin:polys');

        return response()->json([
            'success' => true,
            'message' => 'Service hour updated successfully',
            'data' => $serviceHour,
        ]);
    }

    /**
     * Delete service hour
     */
    public function destroy(string $id)
    {
        $serviceHour = PolyServiceHour::find($id);

        if (!$serviceHour) {
            return response()->json([
                'success' => false,
                'message' => 'Service hour not found',
            ], 404);
        }

        $serviceHour->delete();

        // Invalidate cache
        Cache::forget('info:polys');
        Cache::forget('admin:polys');

        return response()->json([
            'success' => true,
            'message' => 'Service hour deleted successfully',
        ]);
    }
}

