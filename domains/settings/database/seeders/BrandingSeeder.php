<?php

namespace Modules\Settings\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Settings\Models\Branding;

class BrandingSeeder extends Seeder
{
    public function run(): void
    {
        Branding::query()->firstOrCreate([], Branding::DEFAULTS);
    }
}
