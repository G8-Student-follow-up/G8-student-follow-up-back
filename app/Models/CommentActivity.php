<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommentActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'comment_id',
        'card_id',
        'board_id',
        'workspace_id',
        'user_id',
        'activity_date',
        'activity_month',
        'comment_created_at',
    ];

    protected $casts = [
        'activity_date' => 'date',
        'comment_created_at' => 'datetime',
    ];

    /**
     * Build the activity attributes for a given Comment model.
     * Used by CommentObserver, and reusable for backfilling.
     */
    public static function attributesFor(Comment $comment): array
    {
        $comment->loadMissing('card.board');

        if (!$comment->card || !$comment->card->board || !$comment->created_at) {
            throw new \InvalidArgumentException('Comment activity requires a comment with a card, board, and created_at timestamp.');
        }

        $createdAt = $comment->created_at;

        return [
            'comment_id' => $comment->id,
            'card_id' => $comment->card_id,
            'board_id' => $comment->card->board_id,
            'workspace_id' => $comment->card->board->workspace_id,
            'user_id' => $comment->user_id,
            'activity_date' => $createdAt->toDateString(),
            'activity_month' => $createdAt->format('Y-m'),
            'comment_created_at' => $createdAt,
        ];
    }
}