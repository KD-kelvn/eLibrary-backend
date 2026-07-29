<?php

namespace Modules\BookBorrowing\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\BookBorrowing\Models\PenaltyPolicy;

class PenaltyPolicySeeder extends Seeder
{
    public function run(): void
    {
        PenaltyPolicy::query()->updateOrCreate(
            ['name' => 'Default overdue policy'],
            [
                'grace_days' => 0,
                'cost_per_day' => 1000,
                'currency' => 'TZS',
                'is_active' => true,
                'effective_from' => now()->startOfDay(),
                'description' => 'Default policy: overdue from the day after end_date, TZS 1,000 per day.',
            ],
        );
    }
}
