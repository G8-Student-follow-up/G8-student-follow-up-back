<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {

            $table->id();

            $table->foreignId('class_id')
                ->constrained('classes')
                ->cascadeOnDelete();

            $table->foreignId('trainer_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('name');

            $table->string('photo')->nullable();

            $table->text('description')->nullable();

            $table->date('follow_up_date')->nullable();

            $table->enum('priority', [
                'Low',
                'Medium',
                'High'
            ]);

            $table->enum('status', [
                'Pending',
                'In Progress',
                'Completed',
                'Archived'
            ]);

            $table->integer('position')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
