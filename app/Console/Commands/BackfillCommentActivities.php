<?php

namespace App\Console\Commands;

use App\Models\Comment;
use App\Models\CommentActivity;
use Illuminate\Console\Command;

class BackfillCommentActivities extends Command
{
    protected $signature = 'comments:backfill-activity {--chunk=500}';
    protected $description = 'Populate comment_activities for comments that existed before the table was introduced';

    public function handle(): int
    {
        $existingIds = CommentActivity::query()->pluck('comment_id')->all();

        $query = Comment::query()
            ->whereNotIn('id', $existingIds)
            ->with('card.board');

        $total = (clone $query)->count();
        $this->info("Backfilling {$total} comments...");

        $bar = $this->output->createProgressBar($total);

        $query->chunkById((int) $this->option('chunk'), function ($comments) use ($bar) {
            $rows = $comments->map(fn(Comment $comment) => array_merge(
                CommentActivity::attributesFor($comment),
                ['created_at' => now(), 'updated_at' => now()],
            ))->all();

            if (!empty($rows)) {
                CommentActivity::insert($rows);
            }

            $bar->advance(count($rows));
        });

        $bar->finish();
        $this->newLine();
        $this->info('Done.');

        return self::SUCCESS;
    }
}