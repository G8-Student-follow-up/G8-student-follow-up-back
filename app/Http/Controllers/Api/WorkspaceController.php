<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use App\Models\User;
use App\Mail\WorkspaceInvitationMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class WorkspaceController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Workspace::with('creator')
            ->withCount('boards')
            ->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)
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

        $workspace->load('creator', 'boards', 'members.user');

        return response()->json(['workspace' => $workspace]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'nullable|string|max:20',
        ]);

        $validated['created_by'] = $request->user()->id;

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
                    'password' => Hash::make(Str::random(16)),
                    'role' => 'trainer',
                ]
            );
            $userId = $user->id;
        } else {
            $userId = $validated['user_id'];
        }

        WorkspaceMember::firstOrCreate([
            'workspace_id' => $workspace->id,
            'user_id' => $userId,
        ]);

        if (isset($validated['email'])) {
            try {
                $url = env('FRONTEND_URL') . "/app/workspaces/{$workspace->id}";
                Mail::to($user->email)->send(new WorkspaceInvitationMail($request->user(), $workspace, $url));
            } catch (\Exception $e) {
                // Email sending failed, but member was added
            }
        }

        return response()->json(['message' => 'Member added successfully']);
    }

    public function removeMember(Workspace $workspace, $userId)
    {
        $this->ensureAccess($workspace, request()->user());

        WorkspaceMember::where('workspace_id', $workspace->id)
            ->where('user_id', $userId)
            ->delete();

        return response()->json(null, 204);
    }

    private function ensureAccess(Workspace $workspace, $user): void
    {
        $hasAccess = $workspace->where('id', $workspace->id)
            ->where(function ($q) use ($user) {
                $q->where('created_by', $user->id)
                  ->orWhereHas('members', fn($mq) => $mq->where('user_id', $user->id));
            })->exists();

        if (!$hasAccess) {
            abort(403, 'Forbidden');
        }
    }
}
