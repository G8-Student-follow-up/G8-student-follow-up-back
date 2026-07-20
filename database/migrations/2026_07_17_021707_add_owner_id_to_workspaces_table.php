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
<<<<<<< HEAD
        // No-op: owner_id column already exists
=======
        // owner_id column already exists from create_workspaces_table migration
        // Only backfill any null values
        if (Schema::hasColumn('workspaces', 'owner_id')) {
            DB::table('workspaces')->whereNull('owner_id')->update(['owner_id' => 1]);
        }
>>>>>>> 4c54df2c2c23afe4d3288d7ead915d851ea515e5
    }

    public function down(): void
    {
<<<<<<< HEAD
        // No-op: the original migration handles the column lifecycle
=======
        // owner_id column was created in create_workspaces_table migration
        // No action needed - do not drop a column owned by another migration
>>>>>>> 4c54df2c2c23afe4d3288d7ead915d851ea515e5
    }
};
