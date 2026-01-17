<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreStaffRequest;
use App\Http\Requests\Admin\UpdateStaffRequest;
use App\Models\Staff;

class StaffController extends Controller
{
    /**
     * Get all staff
     */
    public function index()
    {
        $staff = Staff::with(['user', 'poly'])->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $staff,
        ]);
    }

    /**
     * Store new staff
     */
    public function store(StoreStaffRequest $request)
    {
        $validated = $request->validated();

        $user = \App\Models\User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'is_active' => $validated['is_active'] ?? true,
        ]);

        $staff = Staff::create([
            'user_id' => $user->id,
            'poly_id' => $validated['poly_id'],
            'code' => $validated['code'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Staff created successfully',
            'data' => $staff->load(['user', 'poly']),
        ], 201);
    }

    /**
     * Get staff detail
     */
    public function show(string $id)
    {
        $staff = Staff::with(['user', 'poly'])->find($id);

        if (!$staff) {
            return response()->json([
                'success' => false,
                'message' => 'Staff not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $staff,
        ]);
    }

    /**
     * Update staff
     */
    public function update(UpdateStaffRequest $request, string $id)
    {
        $staff = Staff::find($id);

        if (!$staff) {
            return response()->json([
                'success' => false,
                'message' => 'Staff not found',
            ], 404);
        }

        $validated = $request->validated();

        $userData = [
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'is_active' => $validated['is_active'] ?? true,
        ];

        if (!empty($validated['password'])) {
            $userData['password'] = bcrypt($validated['password']);
        }

        $staff->user->update($userData);

        $staff->update([
            'poly_id' => $validated['poly_id'],
            'code' => $validated['code'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Staff updated successfully',
            'data' => $staff->load(['user', 'poly']),
        ]);
    }

    /**
     * Delete staff
     */
    public function destroy(string $id)
    {
        $staff = Staff::find($id);

        if (!$staff) {
            return response()->json([
                'success' => false,
                'message' => 'Staff not found',
            ], 404);
        }

        $user = $staff->user;

        // Delete staff first, then user (cascade)
        $staff->delete();
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Staff deleted successfully',
        ]);
    }
}

