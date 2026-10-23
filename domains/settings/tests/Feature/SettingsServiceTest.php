<?php

namespace Modules\Settings\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Modules\Settings\Services\SettingsService;
use Modules\Settings\Support\SettingsCache;
use Tests\TestCase;

/**
 * Service-layer coverage for TC-SET-04 / TC-SET-08 (no HTTP yet).
 *
 * Uses a minimal sqlite schema — full domain migrations require pgsql schemas.
 */
class SettingsServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->createSettingsSchema();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('setting_values');
        Schema::dropIfExists('activity_log');
        parent::tearDown();
    }

    public function test_get_falls_back_to_registry_default(): void
    {
        $settings = app(SettingsService::class);

        $this->assertSame(1, $settings->get('fines.grace_days', 'student'));
        $this->assertSame(500, $settings->get('fines.rate_per_day', 'student'));
    }

    public function test_change_is_effective_immediately_and_append_only(): void
    {
        $settings = app(SettingsService::class);

        $settings->change('fines.grace_days', 3, 'TC-SET-04 raise grace', 'student');

        $this->assertSame(3, $settings->get('fines.grace_days', 'student'));
        $this->assertDatabaseCount('setting_values', 1);
    }

    public function test_future_dated_change_is_not_live_until_effective(): void
    {
        $settings = app(SettingsService::class);

        $settings->change(
            'fines.rate_per_day',
            750,
            'future rate',
            'student',
            now()->addDay(),
        );

        $this->assertSame(500, $settings->get('fines.rate_per_day', 'student'));
        $this->assertSame(
            750,
            $settings->get('fines.rate_per_day', 'student', now()->addDays(2)),
        );
    }

    public function test_cancel_restores_prior_effective_value(): void
    {
        $settings = app(SettingsService::class);

        $settings->change('fines.grace_days', 2, 'first', 'student');
        $future = $settings->change(
            'fines.grace_days',
            9,
            'future',
            'student',
            now()->addHour(),
        );

        $settings->cancel($future->id, 'TC-SET-04 cancel');

        $this->assertSame(2, $settings->get('fines.grace_days', 'student'));
        $this->assertNotNull($future->fresh()->cancelled_at);
    }

    public function test_cache_version_bumps_on_change(): void
    {
        $cache = app(SettingsCache::class);
        $before = $cache->version();

        app(SettingsService::class)->change(
            'fines.block_on_overdue',
            false,
            'bump',
        );

        $this->assertSame($before + 1, $cache->version());
    }

    public function test_activate_pending_bumps_when_future_row_becomes_due(): void
    {
        $settings = app(SettingsService::class);
        $cache = app(SettingsCache::class);

        $settings->change(
            'fines.rate_per_day',
            900,
            'pending activate',
            'student',
            now()->subSecond(),
        );

        // Simulate last activation before this row's effective_from.
        $cache->markActivatedAt(now()->subMinutes(5));
        $before = $cache->version();

        $this->assertSame(1, $settings->activatePending());
        $this->assertSame($before + 1, $cache->version());

        $exit = Artisan::call('settings:activate-pending');
        $this->assertSame(0, $exit);
    }

    public function test_for_role_snapshot(): void
    {
        $snapshot = app(SettingsService::class)->forRole('faculty');

        $this->assertSame('faculty', $snapshot->roleCode);
        $this->assertSame(60, $snapshot->loanPeriodDays);
        $this->assertSame(500.0, $snapshot->fineRatePerDay);
        $this->assertSame(1, $snapshot->graceDays);
    }

    protected function createSettingsSchema(): void
    {
        Schema::dropIfExists('setting_values');
        Schema::dropIfExists('activity_log');

        Schema::create('setting_values', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->string('scope');
            $table->json('value');
            $table->timestamp('effective_from');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->text('reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->index(['key', 'scope', 'effective_from']);
        });

        Schema::create('activity_log', function (Blueprint $table) {
            $table->id();
            $table->string('log_name')->nullable()->index();
            $table->text('description');
            $table->nullableMorphs('subject', 'subject');
            $table->string('event')->nullable();
            $table->nullableMorphs('causer', 'causer');
            $table->json('attribute_changes')->nullable();
            $table->json('properties')->nullable();
            $table->timestamps();
        });
    }
}
