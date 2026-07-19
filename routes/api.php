<?php

use App\Http\Controllers\Api\ActivityController;
use App\Http\Controllers\Api\BoardController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\CalendarController;
use App\Http\Controllers\Api\CardController;
use App\Http\Controllers\Api\ChecklistController;
use App\Models\Attachment;
use App\Http\Controllers\Api\ColumnController;
use App\Http\Controllers\Api\LabelController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\UserController as ApiUserController;
use App\Http\Controllers\Api\WorkspaceController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// ─────────────────────────────────────────────
// PUBLIC ROUTES (no auth)
// 
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// Social Login
Route::get('/auth/{provider}/redirect', [AuthController::class, 'redirectToProvider']);
Route::get('/auth/{provider}/callback', [AuthController::class, 'handleProviderCallback']);

// ─────────────────────────────────────────────
// AUTHENTICATED ROUTES (shared by all roles)
// ─────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'getUser']);
    Route::put('/user', [AuthController::class, 'updateUser']);
    Route::post('/user/avatar', [AuthController::class, 'uploadAvatar']);
});

Route::bind('attachment', fn($value) => Attachment::findOrFail($value));

Route::middleware('auth:sanctum')->group(function () {
    // Workspaces
    Route::get('/workspaces', [WorkspaceController::class, 'index']);
    Route::get('/workspaces/{workspace}', [WorkspaceController::class, 'show']);
    Route::post('/workspaces', [WorkspaceController::class, 'store']);
    Route::put('/workspaces/{workspace}', [WorkspaceController::class, 'update']);
    Route::delete('/workspaces/{workspace}', [WorkspaceController::class, 'destroy']);
    Route::get('/workspaces/{workspace}/members', [WorkspaceController::class, 'members']);
    Route::post('/workspaces/{workspace}/members', [WorkspaceController::class, 'addMember']);
    Route::delete('/workspaces/{workspace}/members/{userId}', [WorkspaceController::class, 'removeMember']);
    Route::get('/workspaces/{workspace}/invitations', [WorkspaceController::class, 'invitations']);
    Route::get('/invitations', [WorkspaceController::class, 'myInvitations']);
    Route::post('/invitations/workspace/{invitation}/accept', [WorkspaceController::class, 'acceptInvitation']);
    Route::post('/invitations/workspace/{invitation}/decline', [WorkspaceController::class, 'declineInvitation']);

    // Boards
    Route::get('/boards', [BoardController::class, 'index']);
    Route::get('/boards/{board}', [BoardController::class, 'show']);
    Route::post('/boards', [BoardController::class, 'store']);
    Route::put('/boards/{board}', [BoardController::class, 'update']);
    Route::delete('/boards/{board}', [BoardController::class, 'destroy']);
    Route::post('/boards/{board}/favorite', [BoardController::class, 'favorite']);
    Route::post('/boards/{board}/archive', [BoardController::class, 'archive']);
    Route::get('/boards/{board}/members', [BoardController::class, 'members']);
    Route::post('/boards/{board}/members', [BoardController::class, 'addMember']);
    Route::delete('/boards/{board}/members/{userId}', [BoardController::class, 'removeMember']);

    // Board Labels
    Route::get('/boards/{board}/labels', [LabelController::class, 'index']);
    Route::post('/boards/{board}/labels', [LabelController::class, 'store']);

    // Labels
    Route::put('/labels/{label}', [LabelController::class, 'update']);
    Route::delete('/labels/{label}', [LabelController::class, 'destroy']);

    // Columns
    Route::post('/columns', [ColumnController::class, 'store']);
    Route::put('/columns/{column}', [ColumnController::class, 'update']);
    Route::delete('/columns/{column}', [ColumnController::class, 'destroy']);
    Route::post('/boards/{board}/columns/reorder', [ColumnController::class, 'reorder']);

    // Cards
    Route::post('/cards', [CardController::class, 'store']);
    Route::put('/cards/{card}', [CardController::class, 'update']);
    Route::delete('/cards/{card}', [CardController::class, 'destroy']);
    Route::put('/cards/{card}/move', [CardController::class, 'move']);
    Route::get('/cards/{card}/comments', [CardController::class, 'comments']);
    Route::post('/cards/{card}/comments', [CardController::class, 'addComment']);
    Route::put('/comments/{comment}', [CardController::class, 'updateComment']);
    Route::delete('/comments/{comment}', [CardController::class, 'destroyComment']);
    Route::get('/cards/{card}/labels', [CardController::class, 'labels']);
    Route::post('/cards/{card}/labels', [CardController::class, 'addLabel']);
    Route::delete('/cards/{card}/labels/{labelId}', [CardController::class, 'removeLabel']);
    Route::get('/cards/{card}/checklists', [CardController::class, 'checklists']);
    Route::post('/cards/{card}/checklists', [CardController::class, 'addChecklist']);
    Route::get('/cards/{card}/attachments', [CardController::class, 'attachments']);
    Route::post('/cards/{card}/attachments', [CardController::class, 'addAttachment']);

    // Attachments
    Route::delete('/attachments/{attachment}', [CardController::class, 'destroyAttachment']);

    // Checklists
    Route::post('/checklists/{checklist}/items', [ChecklistController::class, 'addItem']);
    Route::put('/checklist-items/{checklistItem}', [ChecklistController::class, 'updateItem']);
    Route::delete('/checklist-items/{checklistItem}', [ChecklistController::class, 'destroyItem']);

    // Dashboard
    Route::get('/dashboard/stats', [DashboardController::class, 'stats']);

    // Calendar
    Route::get('/calendar/events', [CalendarController::class, 'events']);

    // Trainers
    Route::get('/trainers', [ApiUserController::class, 'trainers']);

    // Activities
    Route::get('/activities', [ActivityController::class, 'index']);

    // Students
    Route::get('/students', [StudentController::class, 'index']);
    Route::get('/students/{student}', [StudentController::class, 'show']);
    Route::post('/students', [StudentController::class, 'store']);
    Route::put('/students/{student}', [StudentController::class, 'update']);
    Route::delete('/students/{student}', [StudentController::class, 'destroy']);

    // Users (admin only)
    Route::middleware('admin')->group(function () {
        Route::get('/users', [ApiUserController::class, 'index']);
        Route::get('/users/{id}', [ApiUserController::class, 'show']);
        Route::post('/users', [ApiUserController::class, 'store']);
        Route::put('/users/{id}', [ApiUserController::class, 'update']);
        Route::delete('/users/{id}', [ApiUserController::class, 'destroy']);
    });
});
