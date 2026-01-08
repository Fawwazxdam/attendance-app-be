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
        Schema::table('students', function (Blueprint $table) {
            $table->integer('late_free_streak')->default(0)->after('self_contract');
            $table->text('pending_reward')->nullable()->after('late_free_streak');
            $table->boolean('reward_eligible')->default(false)->after('pending_reward');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['late_free_streak', 'pending_reward', 'reward_eligible']);
        });
    }
};
