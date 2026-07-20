<?php

use App\Models\WorkspaceInvitation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workspace_invitations', function (Blueprint $table) {
            $table->string('token', 64)->nullable()->unique()->after('status');
        });

        // Backfill tokens for existing invitations
        WorkspaceInvitation::whereNull('token')->each(function (WorkspaceInvitation $invitation) {
            $invitation->update(['token' => Str::random(64)]);
        });
    }

    public function down(): void
    {
        Schema::table('workspace_invitations', function (Blueprint $table) {
            $table->dropColumn('token');
        });
    }
};
