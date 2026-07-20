<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        // owner_id column already exists from create_workspaces_table migration
        // Only backfill any null values
        if (Schema::hasColumn('workspaces', 'owner_id')) {
            DB::table('workspaces')->whereNull('owner_id')->update(['owner_id' => 1]);
        }
    }

    public function down(): void
    {
        // owner_id column was created in create_workspaces_table migration
        // No action needed - do not drop a column owned by another migration
    }
};
