<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('boards', function (Blueprint $table) {
            $table->index('workspace_id');
            $table->index('is_archived');
        });

        Schema::table('columns', function (Blueprint $table) {
            $table->index('position');
        });

        Schema::table('cards', function (Blueprint $table) {
            $table->index('position');
            $table->index('status');
            $table->index('priority');
            $table->index('created_at');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->index('status');
            $table->index('priority');
            $table->index('class_id');
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->index(['created_at']);
            $table->index(['user_id', 'action']);
        });
    }

    public function down(): void
    {
        Schema::table('boards', function (Blueprint $table) {
            $table->dropIndex(['workspace_id']);
            $table->dropIndex(['is_archived']);
        });

        Schema::table('columns', function (Blueprint $table) {
            $table->dropIndex(['position']);
        });

        Schema::table('cards', function (Blueprint $table) {
            $table->dropIndex(['position']);
            $table->dropIndex(['status']);
            $table->dropIndex(['priority']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['priority']);
            $table->dropIndex(['class_id']);
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
            $table->dropIndex(['user_id', 'action']);
        });
    }
};
