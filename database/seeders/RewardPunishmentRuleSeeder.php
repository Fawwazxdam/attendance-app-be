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
        // $rules = [
        //     [
        //         'type' => 'reward',
        //         'name' => 'Attendance - Present',
        //         'points' => 5,
        //         'description' => 'Automatic reward for present attendance',
        //     ],
        //     [
        //         'type' => 'punishment',
        //         'name' => 'Attendance - Late',
        //         'points' => -5,
        //         'description' => 'Automatic punishment for late attendance',
        //     ],
        //     [
        //         'type' => 'reward',
        //         'name' => 'Excellent Performance',
        //         'points' => 15,
        //         'description' => 'Reward for outstanding academic or behavioral performance',
        //     ],
        //     [
        //         'type' => 'reward',
        //         'name' => 'Good Behavior',
        //         'points' => 10,
        //         'description' => 'Reward for consistent good behavior and participation',
        //     ],
        //     [
        //         'type' => 'punishment',
        //         'name' => 'Minor Violation',
        //         'points' => -10,
        //         'description' => 'Punishment for minor rule violations',
        //     ],
        //     [
        //         'type' => 'punishment',
        //         'name' => 'Serious Violation',
        //         'points' => -25,
        //         'description' => 'Punishment for serious rule violations or misconduct',
        //     ],
        // ];
        $rules = [
            [
                'type' => 'reward',
                'name' => 'Tepat Waktu',
                'points' => 5,
                'description' => 'Siswa datang sebelum bel berbunyi. Mendapat poin positif karena disiplin waktu.',
            ],
            // [
            //     'type' => 'neutral',
            //     'name' => 'Jam Toleransi',
            //     'points' => 0,
            //     'description' => 'Terlambat dalam rentang 1–10 menit. Tidak ada perubahan poin.',
            // ],
            [
                'type' => 'punishment',
                'name' => 'Terlambat',
                'points' => -5,
                'description' => 'Terlambat 16–30 menit dari bel berbunyi. Mendapat pengurangan poin.',
            ],
            [
                'type' => 'punishment',
                'name' => 'Terlambat Parah',
                'points' => -10,
                'description' => 'Terlambat lebih dari 30 menit dari bel berbunyi. Mendapat pengurangan poin lebih besar.',
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
