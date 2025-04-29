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
        Schema::create('task_management', function (Blueprint $table) {
            $table->id();
            $table->uuid('user_id'); // Use UUID to reference users
            $table->string('task_name');
            $table->enum('priority', ['low', 'medium', 'high']);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('deadline');
            $table->enum('status', ['pending', 'in_progress', 'completed']);
            $table->timestamps();

             // Add foreign key constraint
            $table->foreign('user_id')->references('uuid')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('task_management');
    }
};
