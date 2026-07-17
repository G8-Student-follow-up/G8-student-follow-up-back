<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Board;
use App\Models\Card;
use App\Models\Column;
use App\Models\Comment;
use App\Models\Attachment;
use App\Models\Checklist;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CardController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'board_id' => 'required|exists:boards,id',
            'column_id' => 'required|exists:columns,id',
            'student_id' => 'nullable|exists:students,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'position' => 'nullable|integer',
            'priority' => ['sometimes', Rule::in(['Low', 'Medium', 'High'])],
            'status' => ['sometimes', Rule::in(['Pending', 'In Progress', 'Completed', 'Archived'])],
            'follow_up_date' => 'nullable|date',
            'due_date' => 'nullable|date',
            'trainer_id' => 'nullable|exists:users,id',
            'photo' => 'nullable|image|max:10240',
        ]);

        $board = Board::findOrFail($validated['board_id']);
        $this->ensureBoardAccess($board, $request->user());

        $column = Column::findOrFail($validated['column_id']);
        if ((int) $column->board_id !== (int) $validated['board_id']) {
            return response()->json(['message' => 'Column does not belong to the specified board'], 422);
        }

        $validated['created_by'] = $request->user()->id;

        // photo isn't a card column, so pull it out before creating the card
        $photo = $validated['photo'] ?? null;
        unset($validated['photo']);

        $card = Card::create($validated);

        if ($photo) {
            $path = $photo->store('attachments', 'public');

            $attachment = Attachment::create([
                'card_id' => $card->id,
                'file_path' => $path,
                'file_type' => $photo->getClientMimeType(),
                'file_size' => $photo->getSize(),
            ]);

            $card->update(['cover_attachment_id' => $attachment->id]);
        }

        Activity::create([
            'user_id' => $request->user()->id,
            'action' => 'create',
            'subject_type' => 'card',
            'subject_id' => $card->id,
            'changes' => ['description' => "created card {$validated['title']}"],
        ]);

        $card->load(['column', 'labels', 'student', 'trainer', 'attachments', 'coverAttachment']);

        return response()->json(['card' => $card], 201);
    }

    public function update(Request $request, Card $card)
    {
        $this->ensureBoardAccess($card->board, $request->user());

        $validated = $request->validate([
            'column_id' => 'sometimes|exists:columns,id',
            'student_id' => 'nullable|exists:students,id',
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'position' => 'nullable|integer',
            'priority' => ['sometimes', Rule::in(['Low', 'Medium', 'High'])],
            'status' => ['sometimes', Rule::in(['Pending', 'In Progress', 'Completed', 'Archived'])],
            'follow_up_date' => 'nullable|date',
            'due_date' => 'nullable|date',
            'trainer_id' => 'nullable|exists:users,id',
            'cover_attachment_id' => [
                'nullable',
                Rule::exists('attachments', 'id')->where('card_id', $card->id),
            ],
        ]);

        if (isset($validated['column_id'])) {
            $column = Column::findOrFail($validated['column_id']);
            $boardId = $validated['board_id'] ?? $card->board_id;
            if ((int) $column->board_id !== (int) $boardId) {
                return response()->json(['message' => 'Column does not belong to the card board'], 422);
            }
        }

        $card->update($validated);

        Activity::create([
            'user_id' => $request->user()->id,
            'action' => 'update',
            'subject_type' => 'card',
            'subject_id' => $card->id,
            'changes' => ['description' => "updated card {$card->title}"],
        ]);

        $card->load(['column', 'labels', 'student', 'trainer', 'attachments']);

        return response()->json(['card' => $card]);
    }

    public function destroy(Card $card)
    {
        $this->ensureBoardAccess($card->board, request()->user());
        $card->delete();

        return response()->json(null, 204);
    }

    public function move(Request $request, Card $card)
    {
        $this->ensureBoardAccess($card->board, $request->user());

        $validated = $request->validate([
            'column_id' => 'required|exists:columns,id',
            'position' => 'required|integer',
        ]);

        $column = Column::findOrFail($validated['column_id']);
        if ((int) $column->board_id !== (int) $card->board_id) {
            return response()->json(['message' => 'Column does not belong to the same board'], 422);
        }

        $card->update($validated);

        $card->load(['column', 'labels', 'student', 'trainer', 'attachments']);

        return response()->json(['card' => $card]);
    }

    public function comments(Card $card)
    {
        $this->ensureBoardAccess($card->board, request()->user());
        $card->load('comments.user');

        return response()->json(['comments' => $card->comments]);
    }

    public function addComment(Request $request, Card $card)
    {
        $this->ensureBoardAccess($card->board, $request->user());

        $validated = $request->validate([
            'message' => 'required|string',
        ]);

        $comment = Comment::create([
            'card_id' => $card->id,
            'user_id' => $request->user()->id,
            'message' => $validated['message'],
        ]);

        $comment->load('user');

        return response()->json(['comment' => $comment], 201);
    }

    public function updateComment(Request $request, Comment $comment)
    {
        if ($comment->user_id !== $request->user()->id) {
            abort(403);
        }

        $this->ensureBoardAccess($comment->card->board, $request->user());

        $validated = $request->validate([
            'message' => 'required|string',
        ]);

        $comment->update(['message' => $validated['message']]);
        $comment->load('user');

        return response()->json(['comment' => $comment]);
    }

    public function destroyComment(Request $request, Comment $comment)
    {
        if ($comment->user_id !== $request->user()->id) {
            abort(403);
        }

        $this->ensureBoardAccess($comment->card->board, $request->user());

        $comment->delete();

        return response()->json(null, 204);
    }

    public function labels(Card $card)
    {
        $card->load('labels');

        return response()->json(['labels' => $card->labels]);
    }

    public function addLabel(Request $request, Card $card)
    {
        $this->ensureBoardAccess($card->board, $request->user());

        $validated = $request->validate([
            'label_id' => 'required|exists:labels,id',
        ]);

        $card->labels()->syncWithoutDetaching([$validated['label_id']]);

        $card->load('labels');

        return response()->json(['card_label' => $card->labels()->find($validated['label_id'])], 201);
    }

    public function removeLabel(Card $card, $labelId)
    {
        $this->ensureBoardAccess($card->board, request()->user());

        $card->labels()->detach($labelId);

        return response()->json(null, 204);
    }

    public function checklists(Card $card)
    {
        $card->load('checklists.items');

        return response()->json(['checklists' => $card->checklists]);
    }

    public function addChecklist(Request $request, Card $card)
    {
        $this->ensureBoardAccess($card->board, $request->user());

        $validated = $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $checklist = Checklist::create([
            'card_id' => $card->id,
            'title' => $validated['title'],
        ]);

        return response()->json(['checklist' => $checklist], 201);
    }

    public function attachments(Card $card)
    {
        $card->load('attachments');

        return response()->json(['attachments' => $card->attachments]);
    }

    public function addAttachment(Request $request, Card $card)
    {
        $this->ensureBoardAccess($card->board, $request->user());

        $validated = $request->validate([
            'file' => 'required|file|max:10240',
            'file_type' => 'sometimes|string|max:50',
        ]);

        $path = $validated['file']->store('attachments', 'public');

        $attachment = Attachment::create([
            'card_id' => $card->id,
            'file_path' => $path,
            'file_type' => $validated['file_type'] ?? $validated['file']->getClientMimeType(),
            'file_size' => $validated['file']->getSize(),
        ]);

        return response()->json(['attachment' => $attachment], 201);
    }

    public function destroyAttachment(Request $request, Attachment $attachment)
    {
        $this->ensureBoardAccess($attachment->card->board, $request->user());
        Storage::disk('public')->delete($attachment->file_path);

        $attachment->delete();

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
            abort(403, 'Forbidden');
        }
    }
}
