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
        // $users = User::with('roles')->get();
        // return response()->json(['data' => $users]);
    return "success";
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
            'role' => 'required|exists:roles,name'
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password'])
        ]);

        $role = Role::where('name', $validated['role'])->first();
        $user->roles()->attach($role);

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
        // Update user logic
    }

    public function destroy(User $user)
    {
        // Delete user logic
    }
}
