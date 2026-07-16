<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cards', function (Blueprint $table) {
            $table->foreignId('cover_attachment_id')
                ->nullable()
                ->after('due_date')
                ->constrained('attachments')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cards', function (Blueprint $table) {
            $table->dropForeign(['cover_attachment_id']);
            $table->dropColumn('cover_attachment_id');
        });
    }
};
