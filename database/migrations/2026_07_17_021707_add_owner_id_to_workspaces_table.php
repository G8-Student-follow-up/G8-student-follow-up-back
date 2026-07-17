<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->foreignId('owner_id')->nullable()->constrained('users')->cascadeOnDelete();
        });

        
        DB::table('workspaces')->whereNull('owner_id')->update(['owner_id' => 1]);
    }

    public function down(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->dropForeign(['owner_id']);
            $table->dropColumn('owner_id');
        });
    }
};
