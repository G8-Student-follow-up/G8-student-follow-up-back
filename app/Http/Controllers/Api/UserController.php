<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Models\BoardInvitation;
use App\Models\User;
use App\Models\WorkspaceInvitation;
use App\Services\UserService;
use Illuminate\Http\Request;

class UserController extends BaseController

{
    /**
     * Display a listing of the resource.
     */

    public function __construct(
        private UserService $userService
    ) {}
    public function index(Request $request)
    {
        $query = User::query();

        if ($search = $request->search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($role = $request->role) {
            $query->where('role', $role);
        }

        $users = $query->paginate(10);

        return $this->successResponse(
            $users,
            'Users retrieved successfully'
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'sometimes|string|in:admin,trainer',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => $validated['role'] ?? 'trainer',
        ]);

        return $this->successResponse($user, 'User created successfully', 201);
    }

    public function trainers()
    {
        $users = User::where('role', 'trainer')->get(['id', 'name', 'email', 'avatar']);

        return response()->json(['users' => $users]);
    }

    public function invitedEmails(Request $request)
    {
        $userId = $request->user()->id;

        $workspaceIds = WorkspaceInvitation::where('invited_by', $userId)->pluck('user_id');
        $boardIds = BoardInvitation::where('invited_by', $userId)->pluck('user_id');

        $allIds = $workspaceIds->merge($boardIds)->unique()->filter();

        if ($allIds->isEmpty()) {
            return response()->json(['emails' => []]);
        }

        $emails = User::whereIn('id', $allIds)->pluck('email')->unique()->values();

        return response()->json(['emails' => $emails]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = User::find($id);

        if (!$user) {
            return $this->errorResponse(
                'User not found',
                404
            );
        }

        return $this->successResponse(
            $user,
            'User retrieved successfully'
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = User::find($id);

        if (!$user) {
            return $this->errorResponse(
                'User not found',
                404
            );
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'avatar' => 'sometimes|image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $user = $this->userService->update(
            $user,
            $validated
        );

        return $this->successResponse(
            $user,
            'User updated successfully'
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = User::find($id);

        if (!$user) {
            return $this->errorResponse(
                'User not found',
                404
            );
        }


        $this->userService->delete($user);


        return $this->successResponse(
            null,
            'User deleted successfully'
        );
    }
}
