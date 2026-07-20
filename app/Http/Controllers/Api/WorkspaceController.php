<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use App\Models\WorkspaceInvitation;
use App\Models\User;
use App\Mail\WorkspaceInvitationMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class WorkspaceController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Workspace::with('owner')
            ->withCount('boards')
            ->where(function ($q) use ($user) {
                $q->where('owner_id', $user->id)
                  ->orWhereHas('members', fn($mq) => $mq->where('user_id', $user->id));
            });

        if ($search = $request->search) {
            $query->where('name', 'like', "%{$search}%");
        }

        $workspaces = $query->get();

        return response()->json(['workspaces' => $workspaces]);
    }

    public function show(Workspace $workspace)
    {
        $this->ensureAccess($workspace, request()->user());

        $workspace->load('owner', 'boards', 'members.user', 'invitations.user', 'invitations.invitedBy');

        return response()->json(['workspace' => $workspace]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:20',
        ]);

        $validated['owner_id'] = $request->user()->id;

        $workspace = Workspace::create($validated);

        return response()->json(['workspace' => $workspace], 201);
    }

    public function update(Request $request, Workspace $workspace)
    {
        $this->ensureAccess($workspace, $request->user());

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:20',
        ]);

        $workspace->update($validated);

        return response()->json(['workspace' => $workspace]);
    }

    public function destroy(Workspace $workspace)
    {
        $this->ensureAccess($workspace, request()->user());

        $workspace->delete();

        return response()->json(null, 204);
    }

    public function members(Workspace $workspace)
    {
        $this->ensureAccess($workspace, request()->user());

        $workspace->load('members.user');

        return response()->json(['members' => $workspace->members]);
    }

    public function invitations(Workspace $workspace)
    {
        $this->ensureAccess($workspace, request()->user());

        $invitations = $workspace->invitations()
            ->with(['user', 'invitedBy'])
            ->where('status', 'pending')
            ->get();

        return response()->json(['invitations' => $invitations]);
    }

    public function addMember(Request $request, Workspace $workspace)
    {
        $this->ensureAccess($workspace, $request->user());

        $validated = $request->validate([
            'user_id' => 'sometimes|exists:users,id',
            'email' => 'sometimes|email',
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
        if (WorkspaceMember::where(['workspace_id' => $workspace->id, 'user_id' => $userId])->exists()) {
            return response()->json(['message' => 'User is already a member of this workspace'], 409);
        }

        // Create a pending invitation instead of directly adding as member
        $invitation = WorkspaceInvitation::firstOrCreate(
            [
                'workspace_id' => $workspace->id,
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

        // Send invitation email
        try {
            $acceptUrl = env('FRONTEND_URL')
                . "/app/invitations/workspace/{$invitation->id}/accept"
                . "?token={$invitation->token}";

            Mail::to($user->email)->send(new WorkspaceInvitationMail($request->user(), $workspace, $acceptUrl));
        } catch (\Exception $e) {
            // Email sending failed, but invitation was created
        }

        return response()->json(['invitation' => $invitation, 'message' => 'Invitation sent successfully']);
    }

    public function acceptInvitation(Request $request, WorkspaceInvitation $invitation)
    {
        $verification = $this->verifyInvitationAccess($request, $invitation);
        if ($verification !== null) {
            return $verification;
        }

        if (!$invitation->isPending()) {
            return response()->json(['message' => 'Invitation is no longer pending'], 400);
        }

        $invitation->accept();

        return response()->json([
            'message' => 'Invitation accepted successfully',
            'workspace' => $invitation->workspace,
        ]);
    }

    public function declineInvitation(Request $request, WorkspaceInvitation $invitation)
    {
        $verification = $this->verifyInvitationAccess($request, $invitation);
        if ($verification !== null) {
            return $verification;
        }

        if (!$invitation->isPending()) {
            return response()->json(['message' => 'Invitation is no longer pending'], 400);
        }

        $invitation->decline();

        return response()->json(['message' => 'Invitation declined']);
    }

    public function myInvitations(Request $request)
    {
        $invitations = $request->user()->pendingWorkspaceInvitations;

        return response()->json(['invitations' => $invitations]);
    }

    public function removeMember(Workspace $workspace, User $userId)
    {
        $this->ensureAccess($workspace, request()->user());

        WorkspaceMember::where('workspace_id', $workspace->id)
            ->where('user_id', $userId)
            ->delete();

        return response()->json(null, 204);
    }

    private function verifyInvitationAccess(Request $request, WorkspaceInvitation $invitation): ?\Illuminate\Http\JsonResponse
    {
        $user = $request->user();

        // If authenticated, verify the user is the invited user
        if ($user) {
            if ($invitation->user_id !== $user->id) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            return null;
        }

        // If not authenticated, require a valid token
        $token = $request->input('token');

        if (!$invitation->token || !$token || $invitation->token !== $token) {
            return response()->json(['message' => 'Invalid or missing invitation token'], 401);
        }

        return null;
    }

    private function ensureAccess(Workspace $workspace, User $user): void
    {
        $hasAccess = $workspace->where('id', $workspace->id)
            ->where(function ($q) use ($user) {
                $q->where('owner_id', $user->id)
                  ->orWhereHas('members', fn($mq) => $mq->where('user_id', $user->id));
            })->exists();

        if (!$hasAccess) {
            abort(403, 'Forbidden');
        }
    }
}
