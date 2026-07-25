<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            if (!$this->hasIndex('activities', 'activities_action_index')) {
                $table->index('action');
            }
            if (!$this->hasIndex('activities', 'activities_subject_type_subject_id_index')) {
                $table->index(['subject_type', 'subject_id']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropIndex('activities_action_index');
            $table->dropIndex('activities_subject_type_subject_id_index');
        });
    }

    private function hasIndex(string $table, string $index): bool
    {
        try {
            $indexes = DB::select("SHOW INDEXES FROM `{$table}` WHERE Key_name = ?", [$index]);
            return count($indexes) > 0;
        } catch (\Throwable) {
            return false;
        }
    }
};
