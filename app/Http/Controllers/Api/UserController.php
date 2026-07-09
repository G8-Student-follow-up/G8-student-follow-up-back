<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Models\User;
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
    public function index()
    {
        $users = User::paginate(10);

        return $this->successResponse(
            $users,
            'Users retrieved successfully'
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = User::find($id);

        if (!$user) {
            return $this->errorReponse(
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
            return $this->errorReponse(
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
            return $this->errorReponse(
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
