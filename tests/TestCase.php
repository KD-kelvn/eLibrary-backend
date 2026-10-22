<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Local / CI without APP_KEY: bind a disposable key so Feature tests can boot encryption.
        if (blank(config('app.key'))) {
            config([
                'app.key' => 'base64:'.base64_encode(random_bytes(32)),
            ]);
        }
    }
}
