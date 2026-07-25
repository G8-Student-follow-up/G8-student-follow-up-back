<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * One row per comment. Stores the comment's day and month as plain,
     * indexed columns so history/reporting queries don't need DB-specific
     * date functions (DATE_FORMAT / strftime / to_char / FORMAT) and can
     * hit an index instead of scanning + computing on every row.
     */
    public function up(): void
    {
        Schema::create('comment_activities', function (Blueprint $table) {
            $table->id();

            // 1:1 with comments. Cascade delete so history cleans itself up
            // if the underlying comment is removed.
            $table->foreignId('comment_id')
                ->constrained('comments')
                ->cascadeOnDelete();

            // Denormalized foreign keys so history queries can filter
            // without joining back through cards/boards every time.
            $table->foreignId('card_id')->constrained('cards')->cascadeOnDelete();
            $table->foreignId('board_id')->constrained('boards')->cascadeOnDelete();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // activity_date: the calendar day the comment was posted (YYYY-MM-DD)
            // activity_month: the calendar month the comment was posted (YYYY-MM)
            // Both stored as plain columns (not computed at query time) so they
            // can be indexed directly and filtered/grouped cheaply.
            $table->date('activity_date');
            $table->char('activity_month', 7); // e.g. "2026-07"

            // Mirrors comments.created_at so activity rows can be ordered
            // without joining back to comments.
            $table->timestamp('comment_created_at');

            $table->timestamps();

            $table->unique('comment_id');

            // Common access patterns: "this user's activity for a board/month",
            // "this card's activity for a month", "months available for a user".
            $table->index(['user_id', 'board_id', 'activity_month'], 'ca_user_board_month_idx');
            $table->index(['card_id', 'activity_month'], 'ca_card_month_idx');
            $table->index(['card_id', 'activity_date'], 'ca_card_date_idx');
            $table->index('activity_month');
            $table->index('activity_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comment_activities');
    }
};