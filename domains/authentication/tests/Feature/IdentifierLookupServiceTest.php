<?php

namespace Modules\Authentication\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Authentication\Enums\CredentialType;
use Modules\Authentication\Models\User;
use Modules\Authentication\Services\IdentifierLookupService;
use Tests\TestCase;

/**
 * FR-AUTH-01 / V1-010: lookup by phone, email, or username with E.164 normalisation.
 */
class IdentifierLookupServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['audit.enabled' => false]);
        $this->createSchema();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('users');
        parent::tearDown();
    }

    public function test_finds_by_username_email_and_local_phone(): void
    {
        $user = User::query()->create([
            'username' => 'BIT2021001',
            'email' => 'student@example.com',
            'phone' => '+255712345678',
            'password' => 'password',
            'is_blocked' => false,
            'is_active' => true,
            'locale' => 'en',
        ]);

        $lookup = app(IdentifierLookupService::class);

        $this->assertTrue($lookup->find('BIT2021001')?->is($user));
        $this->assertTrue($lookup->find('bit2021001')?->is($user));
        $this->assertTrue($lookup->find('student@example.com')?->is($user));
        $this->assertTrue($lookup->find('0712345678')?->is($user));
        $this->assertTrue($lookup->find('+255 712 345 678')?->is($user));
        $this->assertNull($lookup->find('unknown-user'));

        $this->assertSame(CredentialType::Username, $lookup->detect('BIT2021001'));
        $this->assertSame(CredentialType::Email, $lookup->detect('a@b.co'));
        $this->assertSame(CredentialType::Phone, $lookup->detect('0712345678'));
    }

    public function test_can_authenticate_respects_active_blocked_and_lock(): void
    {
        $user = User::query()->create([
            'username' => 'locked1',
            'email' => 'locked@example.com',
            'password' => 'password',
            'is_blocked' => false,
            'is_active' => true,
            'locked_until' => now()->addMinutes(15),
            'locale' => 'sw',
        ]);

        $this->assertTrue($user->isLocked());
        $this->assertFalse($user->canAuthenticate());

        $user->update(['locked_until' => null, 'is_active' => false]);
        $this->assertFalse($user->fresh()->canAuthenticate());
    }

    protected function createSchema(): void
    {
        Schema::dropIfExists('users');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('phone')->nullable()->unique();
            $table->string('password');
            $table->boolean('is_blocked')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('deactivated_reason')->nullable();
            $table->boolean('break_glass')->default(false);
            $table->timestamp('locked_until')->nullable();
            $table->string('locale', 10)->default('en');
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }
}
