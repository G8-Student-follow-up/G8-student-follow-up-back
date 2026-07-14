<?php

use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\UserController as ApiUserController;
use App\Http\Controllers\Api\WorkspaceController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);


Route::get('/workspaces', [WorkspaceController::class, 'index']);

Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
});

Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::apiResource('students', StudentController::class);

    Route::get('/users', [ApiUserController::class, 'index']);
    Route::get('/users/{id}', [ApiUserController::class, 'show']);
    Route::put('/users/{id}', [ApiUserController::class, 'update']);
    Route::delete('users/{id}', [ApiUserController::class, 'destroy']);

    Route::post('/workspaces', [WorkspaceController::class, 'store']);
    Route::put('/workspaces/{workspace}', [WorkspaceController::class, 'update']);
});

Route::middleware(['auth:sanctum', 'trainer'])->group(function () {
    Route::get('/students', [StudentController::class, 'index']);
    Route::get('/students/{student}', [StudentController::class, 'show']);
    Route::put('/students/{student}', [StudentController::class, 'update']);

    Route::get('/users', [ApiUserController::class, 'index']);
    Route::get('/users/{id}', [ApiUserController::class, 'show']);
    Route::put('/users/{id}', [ApiUserController::class, 'update']);
    Route::delete('users/{id}', [ApiUserController::class, 'destroy']);

    Route::post('/workspaces', [WorkspaceController::class, 'store']);
});