<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE comments MODIFY student_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE attachments MODIFY student_id BIGINT UNSIGNED NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE comments MODIFY student_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE attachments MODIFY student_id BIGINT UNSIGNED NOT NULL');
    }
};
