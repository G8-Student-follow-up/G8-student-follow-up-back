<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Traits\ChecksBoardAccess;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Models\Activity;
use App\Models\Board;
use App\Models\Card;
use App\Models\Column;
use App\Models\Comment;
use App\Models\Attachment;
use App\Models\Checklist;
use App\Mail\MentionNotificationMail;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Resources\CommentResource;

class CardController extends Controller
{
    use ChecksBoardAccess;

    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

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

        $this->notificationService->notifyBoardMembers(
            boardId: $board->id,
            actorId: $request->user()->id,
            type: 'activity',
            title: 'New Card Created',
            message: $request->user()->name . ' created card "' . $validated['title'] . '"',
            data: ['card_id' => $card->id, 'board_id' => $board->id, 'workspace_id' => $board->workspace_id],
            actionUrl: '/app/boards/' . $board->id
        );

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

        $this->notificationService->notifyBoardMembers(
            boardId: $card->board_id,
            actorId: $request->user()->id,
            type: 'activity',
            title: 'Card Updated',
            message: $request->user()->name . ' updated card "' . $card->title . '"',
            data: ['card_id' => $card->id, 'board_id' => $card->board_id, 'workspace_id' => $card->board->workspace_id],
            actionUrl: '/app/boards/' . $card->board_id
        );

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

        $this->notificationService->notifyBoardMembers(
            boardId: $card->board_id,
            actorId: $request->user()->id,
            type: 'activity',
            title: 'Card Moved',
            message: $request->user()->name . ' moved card "' . $card->title . '"',
            data: ['card_id' => $card->id, 'board_id' => $card->board_id, 'workspace_id' => $card->board->workspace_id],
            actionUrl: '/app/boards/' . $card->board_id
        );

        $card->load(['column', 'labels', 'student', 'trainer', 'attachments']);

        return response()->json(['card' => $card]);
    }

    public function comments(Card $card)
    {
        $this->ensureBoardAccess($card->board, request()->user());
        $card->load('comments.user');

        return response()->json([
            'comments' => CommentResource::collection($card->comments),
        ]);
    }

    public function addComment(StoreCommentRequest $request, Card $card)
    {
        $this->ensureBoardAccess($card->board, $request->user());

        $validated = $request->validate([
            'message' => 'required|string',
            'mentioned_user_ids' => 'sometimes|array',
            'mentioned_user_ids.*' => 'integer|exists:users,id',
        ]);

        $comment = Comment::create([
            'card_id' => $card->id,
            'user_id' => $request->user()->id,
            'message' => $request->input('message'),
        ]);

        $comment->load('user');

        Activity::create([
            'user_id' => $request->user()->id,
            'action' => 'create',
            'subject_type' => 'comment',
            'subject_id' => $comment->id,
            'changes' => ['description' => "commented on card \"{$card->title}\""],
        ]);

        $this->notificationService->notifyBoardMembers(
            boardId: $card->board_id,
            actorId: $request->user()->id,
            type: 'comment',
            title: 'New Comment',
            message: $request->user()->name . ' commented: ' . $validated['message'],
            data: ['card_id' => $card->id, 'board_id' => $card->board_id, 'workspace_id' => $card->board->workspace_id, 'comment_id' => $comment->id],
            actionUrl: '/app/boards?board_id=' . $card->board_id . '&workspace_id=' . $card->board->workspace_id . '&card_id=' . $card->id
        );

        // Handle mention notifications
        if (!empty($validated['mentioned_user_ids'])) {
            $actor = $request->user();
            $commentSnippet = strip_tags(substr($validated['message'], 0, 200));
            $actionUrl = config('app.frontend_url') . '/app/boards?board_id=' . $card->board_id . '&workspace_id=' . $card->board->workspace_id . '&card_id=' . $card->id . '&comment_id=' . $comment->id;

            $mentionedUsers = User::whereIn('id', $validated['mentioned_user_ids'])->get();

            foreach ($mentionedUsers as $mentionedUser) {
                // Create in-app notification
                $this->notificationService->createNotification(
                    userId: $mentionedUser->id,
                    type: 'mention',
                    title: 'You have been mentioned in a comment',
                    message: $actor->name . ' mentioned you in "' . $card->title . '"',
                    userName: $actor->name,
                    userAvatar: $actor->avatar_url,
                    data: ['card_id' => $card->id, 'board_id' => $card->board_id, 'workspace_id' => $card->board->workspace_id, 'comment_id' => $comment->id, 'type' => 'mention'],
                    actionUrl: $actionUrl
                );

                // Send email notification
                Mail::to($mentionedUser->email)->queue(new MentionNotificationMail(
                    mentionedBy: $actor,
                    card: $card,
                    commentSnippet: $commentSnippet,
                    actionUrl: $actionUrl
                ));
            }
        }

        return response()->json(['comment' => $comment], 201);
    }

    public function updateComment(UpdateCommentRequest $request, Comment $comment)
    {
        $this->ensureBoardAccess($comment->card->board, $request->user());

        $comment->update(['message' => $request->input('message')]);
        $comment->load('user');

        Activity::create([
            'user_id' => $request->user()->id,
            'action' => 'update',
            'subject_type' => 'comment',
            'subject_id' => $comment->id,
            'changes' => ['description' => "updated a comment on card \"{$comment->card->title}\""],
        ]);

        // Broadcast real-time update
        $board = $comment->card->board;
        $board->loadMissing('workspace.members', 'members');
        $userIds = collect();
        if ($board->workspace) {
            $userIds = $userIds->merge($board->workspace->members->pluck('user_id'));
            if ($board->workspace->owner_id) $userIds->push($board->workspace->owner_id);
        }
        $userIds = $userIds->merge($board->members->pluck('user_id'))
            ->unique()
            ->filter(fn($id) => (int) $id !== (int) $request->user()->id);

        foreach ($userIds as $userId) {
            $notif = \App\Models\Notification::create([
                'user_id' => $userId,
                'type' => 'comment',
                'title' => 'Comment Updated',
                'message' => $request->user()->name . ' updated a comment on "' . $comment->card->title . '"',
                'user_name' => $request->user()->name,
                'user_avatar' => $request->user()->avatar_url,
                'data' => ['card_id' => $comment->card_id, 'board_id' => $comment->card->board_id, 'comment_id' => $comment->id, 'action' => 'updated'],
                'action_url' => '/app/boards/' . $comment->card->board_id,
                'is_read' => false,
            ]);
            $this->notificationService->broadcastToSocket($userId, $notif);
        }

        return response()->json(['comment' => $comment]);
    }

    public function pinComment(Request $request, Comment $comment)
    {
        $this->ensureBoardAccess($comment->card->board, $request->user());

        $comment->update(['is_pinned' => !$comment->is_pinned]);
        $comment->load('user');

        $action = $comment->is_pinned ? 'pinned' : 'unpinned';
        $board = $comment->card->board;
        $board->loadMissing('workspace.members', 'members');
        $userIds = collect();
        if ($board->workspace) {
            $userIds = $userIds->merge($board->workspace->members->pluck('user_id'));
            if ($board->workspace->owner_id) $userIds->push($board->workspace->owner_id);
        }
        $userIds = $userIds->merge($board->members->pluck('user_id'))
            ->unique()
            ->filter(fn($id) => (int) $id !== (int) $request->user()->id);

        foreach ($userIds as $userId) {
            $notif = \App\Models\Notification::create([
                'user_id' => $userId,
                'type' => 'comment',
                'title' => $comment->is_pinned ? 'Comment Pinned' : 'Comment Unpinned',
                'message' => $request->user()->name . " {$action} a comment on \"" . $comment->card->title . '"',
                'user_name' => $request->user()->name,
                'user_avatar' => $request->user()->avatar_url,
                'data' => ['card_id' => $comment->card_id, 'board_id' => $comment->card->board_id, 'comment_id' => $comment->id, 'action' => $action],
                'action_url' => '/app/boards/' . $comment->card->board_id,
                'is_read' => false,
            ]);
            $this->notificationService->broadcastToSocket($userId, $notif);
        }

        return response()->json(['comment' => $comment]);
    }

    public function destroyComment(Request $request, Comment $comment)
    {
        if ($comment->user_id !== $request->user()->id) {
            abort(403, 'You are not authorized to delete this comment.');
        }

        $this->ensureBoardAccess($comment->card->board, $request->user());

        $cardTitle = $comment->card->title;
        $commentId = $comment->id;

        $comment->delete();

        Activity::create([
            'user_id' => $request->user()->id,
            'action' => 'delete',
            'subject_type' => 'comment',
            'subject_id' => $commentId,
            'changes' => ['description' => "deleted a comment on card \"{$cardTitle}\""],
        ]);

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
            'file_type' => 'sometimes|string|max:200',
        ]);

        $path = $validated['file']->store('attachments', 'public');

        $attachment = Attachment::create([
            'card_id' => $card->id,
            'file_path' => $path,
            'file_type' => $validated['file_type'] ?? $validated['file']->getClientMimeType(),
            'file_size' => $validated['file']->getSize(),
        ]);

        $this->notificationService->notifyBoardMembers(
            boardId: $card->board_id,
            actorId: $request->user()->id,
            type: 'attachment',
            title: 'File Uploaded',
            message: $request->user()->name . ' uploaded a file to card "' . $card->title . '"',
            data: ['card_id' => $card->id, 'board_id' => $card->board_id, 'attachment_id' => $attachment->id],
            actionUrl: '/app/boards/' . $card->board_id
        );

        return response()->json(['attachment' => $attachment], 201);
    }

    public function destroyAttachment(Request $request, Attachment $attachment)
    {
        $this->ensureBoardAccess($attachment->card->board, $request->user());
        Storage::disk('public')->delete($attachment->file_path);

        $attachment->delete();

        return response()->json(null, 204);
    }

    /**
     * Toggle the pin status on a comment.
     * PUT /api/comments/{comment}/pin
     */
    public function togglePin(Request $request, Comment $comment)
    {
        $this->ensureBoardAccess($comment->card->board, $request->user());

        $comment->update([
            'is_pinned' => !$comment->is_pinned,
        ]);

        $comment->load('user');

        return response()->json([
            'comment' => new CommentResource($comment),
        ]);
    }
}
