<?php

namespace App\Observers;

use App\Models\Comment;
use App\Models\CommentActivity;

class CommentObserver
{
    /**
     * Record one comment_activities row per new comment.
     */
    public function created(Comment $comment): void
    {
        $comment->loadMissing('card.board');

        CommentActivity::create(CommentActivity::attributesFor($comment));
    }

    /**
     * Keep the activity row's card/board/workspace in sync if a comment
     * is ever reassigned to a different card (rare, but keeps data honest).
     */
    public function updated(Comment $comment): void
    {
        if (!$comment->wasChanged('card_id')) {
            return;
        }

        $comment->loadMissing('card.board');

        CommentActivity::where('comment_id', $comment->id)
            ->update(CommentActivity::attributesFor($comment));
    }

    /**
     * The DB foreign key already cascades on delete, but this keeps
     * behavior explicit and safe if cascade is ever removed.
     */
    public function deleted(Comment $comment): void
    {
        CommentActivity::where('comment_id', $comment->id)->delete();
    }
}