<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * This migration is intentionally a no-op because the `owner_id` column
     * was already created by the original `create_workspaces_table` migration
     * (2026_07_06_012114_create_workspaces_table.php).
     *
     * The column exists as NOT NULL with a foreign key constraint, which is
     * the correct schema since the controller always sets `owner_id` on create.
     */
    public function up(): void
    {
        // No-op: owner_id column already exists
    }

    public function down(): void
    {
        // No-op: the original migration handles the column lifecycle
    }
};
