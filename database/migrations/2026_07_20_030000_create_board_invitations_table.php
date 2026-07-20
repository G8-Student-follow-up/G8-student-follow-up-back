<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('board_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('invited_by')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->string('token', 64)->nullable()->unique();
            $table->timestamps();

            $table->unique(['board_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('board_invitations');
    }
};
