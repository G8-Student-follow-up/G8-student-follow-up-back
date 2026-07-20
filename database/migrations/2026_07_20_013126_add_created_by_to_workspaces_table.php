<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->foreignId('created_by')
                ->nullable()
                ->after('owner_id')
                ->constrained('users')
                ->nullOnDelete();
        });

        // Backfill created_by from owner_id for existing workspaces
        DB::table('workspaces')->whereNull('created_by')->update(['created_by' => DB::raw('owner_id')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
        });
    }
};
