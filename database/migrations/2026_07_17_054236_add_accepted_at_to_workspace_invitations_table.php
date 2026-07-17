<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('workspace_invitations', 'accepted_at')) {
            Schema::table('workspace_invitations', function (Blueprint $table) {
                $table->timestamp('accepted_at')->nullable()->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('workspace_invitations', 'accepted_at')) {
            Schema::table('workspace_invitations', function (Blueprint $table) {
                $table->dropColumn('accepted_at');
            });
        }
    }
};