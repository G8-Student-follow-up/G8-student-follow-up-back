<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('boards', function (Blueprint $table) {
            $table->text('description')->nullable()->after('title');
            $table->string('color', 20)->nullable()->after('description');
            $table->string('background', 255)->nullable()->after('color');
        });
    }

    public function down(): void
    {
        Schema::table('boards', function (Blueprint $table) {
            $table->dropColumn(['description', 'color', 'background']);
        });
    }
};
