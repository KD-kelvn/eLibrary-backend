<?php

namespace Modules\BookBorrowing\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\BookBorrowing\Models\PenaltyPolicy;

/**
 * @deprecated Prefer settings.setting_values / Library policy §8 defaults
 *             (fines.grace_days=1, fines.rate_per_day=500). Kept so existing
 *             admin penalty-policy screens still have a seed row until V1-001a.
 */
class PenaltyPolicySeeder extends Seeder
{
    public function run(): void
    {
        PenaltyPolicy::query()->updateOrCreate(
            ['name' => 'Default overdue policy'],
            [
                'grace_days' => 1,
                'cost_per_day' => 500,
                'currency' => 'TZS',
                'is_active' => true,
                'effective_from' => now()->startOfDay(),
                'description' => 'Deprecated seed aligned with Library policy §8 (V1-001). Accrual uses SettingsService.',
            ],
        );
    }
}
