<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;

class SystemSettingController extends Controller
{
    /**
     * Get all system settings
     */
    public function index()
    {
        $settings = SystemSetting::all()->mapWithKeys(function ($setting) {
            return [
                $setting->key => [
                    'value' => $setting->value,
                    'description' => $setting->description,
                ]
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }

    /**
     * Update system settings (batch update)
     * 
     * @bodyParam settings array required Array of key-value pairs to update
     * @bodyParam settings.*.key string required Setting key
     * @bodyParam settings.*.value string required Setting value
     * @bodyParam settings.*.description string optional Setting description
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'required|string',
            'settings.*.description' => 'nullable|string',
        ]);

        $updatedSettings = [];

        foreach ($validated['settings'] as $setting) {
            $updated = SystemSetting::set(
                $setting['key'],
                $setting['value'],
                $setting['description'] ?? null
            );
            $updatedSettings[] = $updated;
        }

        return response()->json([
            'success' => true,
            'message' => 'System settings updated successfully',
            'data' => $updatedSettings,
        ]);
    }

    /**
     * Get a single system setting by key
     */
    public function show(string $key)
    {
        $setting = SystemSetting::where('key', $key)->first();

        if (!$setting) {
            return response()->json([
                'success' => false,
                'message' => 'Setting not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $setting,
        ]);
    }
}
