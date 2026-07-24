<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Board;
use App\Models\BoardInvitation;
use App\Models\BoardMember;
use App\Models\User;
use App\Mail\BoardInvitationMail;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class BoardController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Board::with('workspace')
            ->withCount('columns', 'cards')
            ->where(function ($q) use ($user) {
                $q->whereHas('workspace', function ($wq) use ($user) {
                    $wq->where('owner_id', $user->id)
                        ->orWhereHas('members', fn($mq) => $mq->where('user_id', $user->id));
                })->orWhereHas('members', fn($bq) => $bq->where('user_id', $user->id));
            });

        if ($workspaceId = $request->workspace_id) {
            $query->where('workspace_id', $workspaceId);
        }

        $boards = $query->get();

        return response()->json(['boards' => $boards]);
    }

    public function show(Request $request, Board $board)
    {
        $this->ensureBoardAccess($board, $request->user());

        $board->load([
            'workspace',
            'columns' => fn($q) => $q->orderBy('position'),
            'columns.cards' => fn($q) => $q->orderBy('position'),
            'columns.cards.comments' => fn($q) => $q->with('user')->latest(),
            'columns.cards.attachments',
            'columns.cards.checklists' => fn($q) => $q->with('items'),
            'columns.cards.labels',
            'columns.cards.student',
            'columns.cards.trainer',
            'members.user',
            'labels',
        ]);

        return response()->json(['board' => $board]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'workspace_id' => 'required|exists:workspaces,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:20',
            'background' => 'nullable|string|max:255',
        ]);

        $this->ensureWorkspaceAccess($validated['workspace_id'], $request->user());

        $board = Board::create($validated);

        return response()->json(['board' => $board], 201);
    }

    public function update(Request $request, Board $board)
    {
        $this->ensureBoardAccess($board, $request->user());

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:20',
            'background' => 'nullable|string|max:255',
            'workspace_id' => 'sometimes|exists:workspaces,id',
        ]);

        $board->update($validated);

        return response()->json(['board' => $board]);
    }

    public function destroy(Request $request, Board $board)
    {
        $this->ensureBoardAccess($board, $request->user());

        $board->delete();

        return response()->json(null, 204);
    }

    public function favorite(Request $request, Board $board)
    {
        $this->ensureBoardAccess($board, $request->user());

        $board->update(['is_favorite' => !$board->is_favorite]);

        return response()->json(['board' => $board]);
    }

    public function archive(Request $request, Board $board)
    {
        $this->ensureBoardAccess($board, $request->user());

        $board->update(['is_archived' => !$board->is_archived]);

        return response()->json(['board' => $board]);
    }

    public function members(Request $request, Board $board)
    {
        $this->ensureBoardAccess($board, $request->user());

        $board->load('members.user');

        return response()->json(['members' => $board->members]);
    }

    public function addMember(Request $request, Board $board)
    {
        $this->ensureBoardAccess($board, $request->user());

        $validated = $request->validate([
            'user_id' => 'sometimes|exists:users,id',
            'email' => 'sometimes|email',
            'role' => 'sometimes|string|max:50',
        ]);

        if (isset($validated['email'])) {
            $user = User::firstOrCreate(
                ['email' => $validated['email']],
                [
                    'name' => explode('@', $validated['email'])[0],
                    'password' => Str::random(16),
                    'role' => 'trainer',
                ]
            );
            $userId = $user->id;
        } else {
            $user = User::findOrFail($validated['user_id']);
            $userId = $user->id;
        }

        // Don't invite if the user is already a member
        if (BoardMember::where(['board_id' => $board->id, 'user_id' => $userId])->exists()) {
            return response()->json(['message' => 'User is already a member of this board'], 409);
        }

        // Create a pending invitation instead of directly adding as member
        $invitation = BoardInvitation::firstOrCreate(
            [
                'board_id' => $board->id,
                'user_id' => $userId,
            ],
            [
                'invited_by' => $request->user()->id,
                'status' => 'pending',
            ]
        );

        // If invitation already existed and was declined, reset it
        if ($invitation->wasRecentlyCreated === false && $invitation->status === 'declined') {
            $invitation->update([
                'invited_by' => $request->user()->id,
                'status' => 'pending',
            ]);
        }

        $invitation->load('user', 'invitedBy');

        if (isset($validated['email'])) {
            // Queue the invitation email instead of sending it inline. A slow or unreachable
            // SMTP server (very common on school/shared networks) would otherwise block this
            // whole request for 30-60+ seconds and make the invite look "broken" even though
            // the invitation row above was already created successfully.
            try {
                $acceptUrl = env('FRONTEND_URL')
                    . "/app/invitations/board/{$invitation->id}/accept"
                    . "?token={$invitation->token}";
                Mail::to($user->email)->queue(new BoardInvitationMail($request->user(), $board, $acceptUrl));
            } catch (\Throwable $e) {
                // Email failed to queue, but the invitation still exists — log it instead of
                // silently losing the failure reason.
                Log::warning('Board invitation email failed to queue: ' . $e->getMessage());
            }
        }

        // Create in-app notification for the invited user, so they can accept it
        // without needing the email if they already have an account.
        $this->notificationService->notifyUser(
            userId: $userId,
            actorId: $request->user()->id,
            type: 'invite',
            title: 'Board Invitation',
            message: "{$request->user()->name} invited you to join \"{$board->title}\"",
            data: ['board_id' => $board->id, 'invitation_id' => $invitation->id],
            actionUrl: "/app/invitations"
        );

        return response()->json(['invitation' => $invitation, 'message' => 'Invitation sent successfully'], 201);
    }

    public function myInvitations(Request $request)
    {
        $invitations = $request->user()->pendingBoardInvitations;

        return response()->json(['invitations' => $invitations]);
    }

    public function invitations(Request $request, Board $board)
    {
        $user = $request->user();

        $hasPendingInvite = $board->invitations()
            ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->exists();

        if (!$hasPendingInvite) {
            $this->ensureBoardAccess($board, $user);
        }

        $board->load(['invitations' => fn($q) => $q->with('user', 'invitedBy')]);

        return response()->json(['invitations' => $board->invitations]);
    }

    public function acceptInvitation(Request $request, BoardInvitation $invitation)
    {
        $verification = $this->verifyInvitationAccess($request, $invitation);
        if ($verification !== null) {
            return $verification;
        }

        if (!$invitation->isPending()) {
            return response()->json(['message' => 'Invitation is no longer pending'], 400);
        }

        $invitation->accept();

        $invitation->load('board');

        // Clear invitation notifications for this user
        \App\Models\Notification::forUser($invitation->user_id)
            ->where('type', 'invite')
            ->where('is_read', false)
            ->get()
            ->each(function ($notif) use ($invitation) {
                $notifData = $notif->data ?? [];
                if (isset($notifData['board_id']) && (int) $notifData['board_id'] === (int) $invitation->board_id) {
                    $notif->markAsRead();
                }
            });

        return response()->json([
            'message' => 'Invitation accepted successfully',
            'board' => $invitation->board,
        ]);
    }

    public function declineInvitation(Request $request, BoardInvitation $invitation)
    {
        $verification = $this->verifyInvitationAccess($request, $invitation);
        if ($verification !== null) {
            return $verification;
        }

        if (!$invitation->isPending()) {
            return response()->json(['message' => 'Invitation is no longer pending'], 400);
        }

        $invitation->decline();

        // Also mark related notifications as read
        \App\Models\Notification::forUser($invitation->user_id)
            ->where('type', 'invite')
            ->where('is_read', false)
            ->get()
            ->each(function ($notif) use ($invitation) {
                $notifData = $notif->data ?? [];
                if (isset($notifData['board_id']) && (int) $notifData['board_id'] === (int) $invitation->board_id) {
                    $notif->markAsRead();
                }
            });

        return response()->json(['message' => 'Invitation declined']);
    }

    public function removeMember(Request $request, Board $board, $userId)
    {
        $this->ensureBoardAccess($board, $request->user());

        $deleted = DB::table('board_members')
            ->where('board_id', $board->id)
            ->where('user_id', $userId)
            ->delete();

        if ($deleted === 0) {
            return response()->json(['message' => 'Member not found'], 404);
        }

        return response()->json(null, 204);
    }

    private function verifyInvitationAccess(Request $request, BoardInvitation $invitation): ?\Illuminate\Http\JsonResponse
    {
        $user = $request->user('sanctum');

        if ($user && $invitation->user_id === $user->id) {
            return null;
        }

        $token = $request->input('token');

        if ($invitation->token && $token && $invitation->token === $token) {
            return null;
        }

        return response()->json(['message' => 'Invalid or missing invitation token'], 401);
    }

    private function ensureBoardAccess(Board $board, $user): void
    {
        $hasWorkspaceAccess = $board->workspace()
            ->where(function ($q) use ($user) {
                $q->where('owner_id', $user->id)
                    ->orWhereHas('members', fn($mq) => $mq->where('user_id', $user->id));
            })->exists();

        $hasBoardMembership = $board->members()->where('user_id', $user->id)->exists();

        if (!$hasWorkspaceAccess && !$hasBoardMembership) {
            abort(403);
        }
    }

    private function ensureWorkspaceAccess(int $workspaceId, $user): void
    {
        $hasAccess = \App\Models\Workspace::where('id', $workspaceId)
            ->where(function ($q) use ($user) {
                $q->where('owner_id', $user->id)
                    ->orWhereHas('members', fn($mq) => $mq->where('user_id', $user->id));
            })->exists();

        if (!$hasAccess) {
            abort(403);
        }
    }
}