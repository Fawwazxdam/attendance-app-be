<?php

namespace Database\Seeders;

use App\Models\RewardPunishmentRule;
use Illuminate\Database\Seeder;

class RewardPunishmentRuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rules = [
            [
                'type' => 'reward',
                'name' => 'Attendance - Present',
                'points' => 5,
                'description' => 'Automatic reward for present attendance',
            ],
            [
                'type' => 'punishment',
                'name' => 'Attendance - Late',
                'points' => -5,
                'description' => 'Automatic punishment for late attendance',
            ],
            [
                'type' => 'reward',
                'name' => 'Excellent Performance',
                'points' => 15,
                'description' => 'Reward for outstanding academic or behavioral performance',
            ],
            [
                'type' => 'reward',
                'name' => 'Good Behavior',
                'points' => 10,
                'description' => 'Reward for consistent good behavior and participation',
            ],
            [
                'type' => 'punishment',
                'name' => 'Minor Violation',
                'points' => -10,
                'description' => 'Punishment for minor rule violations',
            ],
            [
                'type' => 'punishment',
                'name' => 'Serious Violation',
                'points' => -25,
                'description' => 'Punishment for serious rule violations or misconduct',
            ],
        ];

        foreach ($rules as $rule) {
            RewardPunishmentRule::firstOrCreate(
                ['name' => $rule['name']],
                $rule
            );
        }
    }
}