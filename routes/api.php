<?php

use App\Http\Controllers\ClassController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\UserController as ApiUserController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WorkspaceController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

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

    Route::apiResource('workspaces', WorkspaceController::class);
    Route::post('/workspaces/{workspace}/invite', [WorkspaceController::class, 'inviteTrainer']);
    Route::delete('/workspaces/{workspace}/members/{user}', [WorkspaceController::class, 'removeMember']);

    Route::get('/classes', [ClassController::class, 'index']);
    Route::post('/classes', [ClassController::class, 'store']);
    Route::put('/classes/reorder', [ClassController::class, 'reorder']);
    Route::get('/classes/{classRoom}', [ClassController::class, 'show']);
    Route::put('/classes/{classRoom}', [ClassController::class, 'update']);
    Route::delete('/classes/{classRoom}', [ClassController::class, 'destroy']);
});

Route::middleware(['auth:sanctum', 'trainer'])->group(function () {
    Route::get('/students', [StudentController::class, 'index']);
    Route::get('/students/{student}', [StudentController::class, 'show']);
    Route::put('/students/{student}', [StudentController::class, 'update']);

    Route::get('/users', [ApiUserController::class, 'index']);
    Route::get('/users/{id}', [ApiUserController::class, 'show']);
    Route::put('/users/{id}', [ApiUserController::class, 'update']);
    Route::delete('/users/{id}', [ApiUserController::class, 'destroy']);
});