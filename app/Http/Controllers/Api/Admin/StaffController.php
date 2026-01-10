<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class StaffController extends Controller
{
    /**
     * Get all staff
     */
    public function index()
    {
        $staff = \App\Models\Staff::with(['user', 'poly'])->get();

        return response()->json([
            'success' => true,
            'data' => $staff,
        ]);
    }

    /**
     * Store new staff
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'username' => 'required|string|unique:users,username',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'poly_id' => 'required|exists:polys,id',
            'code' => 'required|string|unique:staff,code',
            'is_active' => 'boolean',
        ]);

        $user = \App\Models\User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'is_active' => $validated['is_active'] ?? true,
        ]);

        $staff = \App\Models\Staff::create([
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
        $staff = \App\Models\Staff::with(['user', 'poly'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $staff,
        ]);
    }

    /**
     * Update staff
     */
    public function update(Request $request, string $id)
    {
        $staff = \App\Models\Staff::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string',
            'username' => 'required|string|unique:users,username,' . $staff->user_id,
            'email' => 'required|email|unique:users,email,' . $staff->user_id,
            'password' => 'nullable|string|min:8',
            'poly_id' => 'required|exists:polys,id',
            'code' => 'required|string|unique:staff,code,' . $id,
            'is_active' => 'boolean',
        ]);

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
        $staff = \App\Models\Staff::findOrFail($id);
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
