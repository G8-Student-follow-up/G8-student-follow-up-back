<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Socket server URL for real-time push.
     * Falls back to null if not configured, so notifications still work via API polling.
     */
    protected ?string $socketHost;

    public function __construct()
    {
        $this->socketHost = env('SOCKET_HOST', 'http://localhost:3001');
    }

    /**
     * Create a notification for a specific user.
     */
    public function createNotification(
        int $userId,
        string $type,
        string $title,
        ?string $message = null,
        ?string $userName = null,
        ?string $userAvatar = null,
        ?array $data = null,
        ?string $actionUrl = null
    ): Notification {
        $notification = Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'user_name' => $userName,
            'user_avatar' => $userAvatar,
            'data' => $data,
            'action_url' => $actionUrl,
            'is_read' => false,
        ]);

        // Push real-time notification via socket server
        $this->broadcastToSocket($userId, $notification);

        return $notification;
    }

    /**
     * Notify all members of a board (except the actor).
     */
    public function notifyBoardMembers(
        int $boardId,
        int $actorId,
        string $type,
        string $title,
        ?string $message = null,
        ?array $data = null,
        ?string $actionUrl = null
    ): void {
        $board = \App\Models\Board::with(['workspace.members', 'members'])->find($boardId);
        if (!$board) {
            return;
        }

        $actor = User::find($actorId);
        $userName = $actor?->name;
        $userAvatar = $actor?->avatar_url;

        // Collect all user IDs from workspace members and board members
        $userIds = collect();

        if ($board->workspace) {
            $workspaceMemberIds = $board->workspace->members->pluck('user_id');
            $userIds = $userIds->merge($workspaceMemberIds);
        }

        $boardMemberIds = $board->members->pluck('user_id');
        $userIds = $userIds->merge($boardMemberIds);

        // Also notify the workspace owner
        if ($board->workspace && $board->workspace->owner_id) {
            $userIds->push($board->workspace->owner_id);
        }

        // Remove the actor from the list
        $userIds = $userIds->unique()->filter(fn($id) => (int) $id !== (int) $actorId);

        foreach ($userIds as $userId) {
            $this->createNotification(
                userId: $userId,
                type: $type,
                title: $title,
                message: $message,
                userName: $userName,
                userAvatar: $userAvatar,
                data: $data,
                actionUrl: $actionUrl
            );
        }
    }

    /**
     * Notify a specific user (used for invitations).
     */
    public function notifyUser(
        int $userId,
        int $actorId,
        string $type,
        string $title,
        ?string $message = null,
        ?array $data = null,
        ?string $actionUrl = null
    ): Notification {
        $actor = User::find($actorId);

        return $this->createNotification(
            userId: $userId,
            type: $type,
            title: $title,
            message: $message,
            userName: $actor?->name,
            userAvatar: $actor?->avatar_url,
            data: $data,
            actionUrl: $actionUrl
        );
    }

    /**
     * Notify all workspace members (except the actor).
     */
    public function notifyWorkspaceMembers(
        int $workspaceId,
        int $actorId,
        string $type,
        string $title,
        ?string $message = null,
        ?array $data = null,
        ?string $actionUrl = null
    ): void {
        $workspace = \App\Models\Workspace::with('members')->find($workspaceId);
        if (!$workspace) {
            return;
        }

        $actor = User::find($actorId);
        $userName = $actor?->name;
        $userAvatar = $actor?->avatar_url;

        $userIds = $workspace->members->pluck('user_id')
            ->push($workspace->owner_id)
            ->unique()
            ->filter(fn($id) => (int) $id !== (int) $actorId);

        foreach ($userIds as $userId) {
            $this->createNotification(
                userId: $userId,
                type: $type,
                title: $title,
                message: $message,
                userName: $userName,
                userAvatar: $userAvatar,
                data: $data,
                actionUrl: $actionUrl
            );
        }
    }

    /**
     * Send a real-time notification push to the socket server.
     * The socket server will emit the notification to the user's connected sockets.
     */
    protected function broadcastToSocket(int $userId, Notification $notification): void
    {
        if (!$this->socketHost) {
            return;
        }

        try {
            Http::timeout(1)->post($this->socketHost . '/emit', [
                'userId' => (string) $userId,
                'notification' => $notification->toArray(),
            ]);
        } catch (\Exception $e) {
            // Socket server unreachable is not critical — notifications still work via API polling
            Log::debug('Failed to push notification to socket server: ' . $e->getMessage());
        }
    }

    /**
     * Notify mentioned users in a comment.
     */
    public function notifyMentionedUsers(
        array $mentionedUserIds,
        int $actorId,
        string $message,
        Card $card
    ): void {
        $actor = User::find($actorId);
        
        foreach ($mentionedUserIds as $userId) {
            // Create in-app notification
            $this->createNotification(
                userId: $userId,
                type: 'mention',
                title: 'You were mentioned',
                message: $actor?->name . ' mentioned you in a comment: ' . $message,
                userName: $actor?->name,
                userAvatar: $actor?->avatar_url,
                data: [
                    'card_id' => $card->id,
                    'board_id' => $card->board_id,
                    'workspace_id' => $card->board->workspace_id,
                ],
                actionUrl: '/app/boards/' . $card->board_id
            );

            // Send email notification
            try {
                \Mail::to(User::find($userId))->send(
                    new \App\Mail\MentionNotificationMail($actor, $card, $message)
                );
            } catch (\Exception $e) {
                Log::debug('Failed to send mention email: ' . $e->getMessage());
            }
        }
    }
}
