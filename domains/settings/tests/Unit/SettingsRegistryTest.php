<?php

namespace Modules\Settings\Tests\Unit;

use Modules\Settings\Support\SettingsRegistry;
use Tests\TestCase;

class SettingsRegistryTest extends TestCase
{
    public function test_registry_covers_section_eight_fine_defaults(): void
    {
        $registry = app(SettingsRegistry::class);

        $this->assertTrue($registry->exists('fines.grace_days'));
        $this->assertTrue($registry->exists('fines.rate_per_day'));
        $this->assertTrue($registry->isRoleScoped('fines.grace_days'));

        $this->assertSame(1, $registry->default('fines.grace_days', 'student'));
        $this->assertSame(500, $registry->default('fines.rate_per_day', 'student'));
        $this->assertSame(14, $registry->default('loans.period_days', 'student'));
        $this->assertSame(60, $registry->default('loans.period_days', 'faculty'));
    }

    public function test_normalize_scope(): void
    {
        $registry = app(SettingsRegistry::class);

        $this->assertSame('global', $registry->normalizeScope('files.book_max_mb'));
        $this->assertSame('role:faculty', $registry->normalizeScope('fines.grace_days', 'faculty'));
    }
}
