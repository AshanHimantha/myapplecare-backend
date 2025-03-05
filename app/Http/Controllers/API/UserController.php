<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    // CRUD operations
    public function index()
    {
        $users = User::with('roles')->get();
        return response()->json(['data' => $users]);

    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,name',
            'status' => 'sometimes|in:active,inactive'
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'status' => $validated['status'] ?? 'active'
        ]);

        // Get all roles and attach them to the user
        $roles = Role::whereIn('name', $validated['roles'])->get();
        $user->roles()->attach($roles);

        return response()->json([
            'status' => 'success',
            'data' => $user->load('roles')
        ], 201);
    }

    public function show(User $user)
    {
        return response()->json(['data' => $user->load('roles')]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string',
            'email' => 'sometimes|required|email|unique:users,email,' . $user->id,
            'password' => 'sometimes|required|min:6',
            'roles' => 'sometimes|required|array',
            'roles.*' => 'exists:roles,name',
            'status' => 'sometimes|in:active,inactive'
        ]);

        // Update user details
        if (isset($validated['name'])) {
            $user->name = $validated['name'];
        }
        if (isset($validated['email'])) {
            $user->email = $validated['email'];
        }
        if (isset($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }
        if (isset($validated['status'])) {
            $user->status = $validated['status'];
        }
        $user->save();

        // Update roles if provided
        if (isset($validated['roles'])) {
            $roles = Role::whereIn('name', $validated['roles'])->get();
            $user->roles()->sync($roles);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'User updated successfully',
            'data' => $user->load('roles')
        ]);
    }

    public function destroy(User $user)
    {
        // Delete user logic
    }

    public function updateStatus(Request $request, User $user)
    {
        $validated = $request->validate([
            'status' => 'required|in:active,inactive'
        ]);

        $user->status = $validated['status'];
        $user->save();

        return response()->json([
            'status' => 'success',
            'message' => 'User status updated successfully',
            'data' => $user->load('roles')
        ]);
    }
}
