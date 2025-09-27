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
        Schema::create('reward_punishment_log', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('student_id')->constrained('student')->onDelete('cascade')->onUpdate('cascade');
            $table->foreignId('rules_id')->constrained('reward_punishment_rules')->onDelete('cascade')->onUpdate('cascade');
            $table->date('date');
            $table->foreignId('given_by')->constrained('teacher')->onDelete('cascade')->onUpdate('cascade');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reward_punishment_log');
    }
};
