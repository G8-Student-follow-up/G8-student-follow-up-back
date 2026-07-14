<?php

use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\UserController as ApiUserController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
});

// Authenticated routes
Route::middleware('auth:sanctum')->group(function () {
    // ─── Students (admin: full CRUD, trainer: read + update) ───
    Route::get('/students', [StudentController::class, 'index'])
        ->middleware('role:admin,trainer');
    Route::get('/students/{student}', [StudentController::class, 'show'])
        ->middleware('role:admin,trainer');
    Route::put('/students/{student}', [StudentController::class, 'update'])
        ->middleware('role:admin,trainer');
    Route::post('/students', [StudentController::class, 'store'])
        ->middleware('role:admin');
    Route::delete('/students/{student}', [StudentController::class, 'destroy'])
        ->middleware('role:admin');

    // ─── Users (admin + trainer) ───
    Route::get('/users', [ApiUserController::class, 'index'])
        ->middleware('role:admin,trainer');
    Route::get('/users/{id}', [ApiUserController::class, 'show'])
        ->middleware('role:admin,trainer');
    Route::put('/users/{id}', [ApiUserController::class, 'update'])
        ->middleware('role:admin,trainer');
    Route::delete('users/{id}', [ApiUserController::class, 'destroy'])
        ->middleware('role:admin,trainer');

    // ─── Workspaces ───
    Route::get('/workspaces', [App\Http\Controllers\Api\WorkspaceController::class, 'index']);
    Route::post('/workspaces', [App\Http\Controllers\Api\WorkspaceController::class, 'store']);
    Route::get('/workspaces/{workspace}', [App\Http\Controllers\Api\WorkspaceController::class, 'show']);
    Route::put('/workspaces/{workspace}', [App\Http\Controllers\Api\WorkspaceController::class, 'update']);
    Route::delete('/workspaces/{workspace}', [App\Http\Controllers\Api\WorkspaceController::class, 'destroy']);

    // ─── Boards (nested under workspaces) ───
    Route::get('/workspaces/{workspace}/boards', [App\Http\Controllers\Api\BoardController::class, 'index']);
    Route::post('/workspaces/{workspace}/boards', [App\Http\Controllers\Api\BoardController::class, 'store']);
    Route::get('/workspaces/{workspace}/boards/{board}', [App\Http\Controllers\Api\BoardController::class, 'show']);
    Route::put('/workspaces/{workspace}/boards/{board}', [App\Http\Controllers\Api\BoardController::class, 'update']);
    Route::delete('/workspaces/{workspace}/boards/{board}', [App\Http\Controllers\Api\BoardController::class, 'destroy']);
});