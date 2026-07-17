<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Board;
use App\Models\BoardMember;
use App\Models\User;
use App\Mail\BoardInvitationMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class BoardController extends Controller
{
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
            $userId = $validated['user_id'];
        }

        $member = BoardMember::create([
            'board_id' => $board->id,
            'user_id' => $userId,
            'role' => $validated['role'] ?? 'member',
        ]);

        $member->load('user');

        if (isset($validated['email'])) {
            try {
                $url = env('FRONTEND_URL') . "/app/boards?board_id={$board->id}";
                Mail::to($user->email)->send(new BoardInvitationMail($request->user(), $board, $url));
            } catch (\Exception $e) {
                // Email sending failed, but member was added
            }
        }

        return response()->json(['member' => $member], 201);
    }

    public function removeMember(Request $request, Board $board, $userId)
    {
        $this->ensureBoardAccess($board, $request->user());

        BoardMember::where('board_id', $board->id)
            ->where('user_id', $userId)
            ->delete();

        return response()->json(null, 204);
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
