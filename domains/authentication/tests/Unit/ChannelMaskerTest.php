<?php

namespace Modules\Authentication\Tests\Unit;

use Modules\Authentication\Support\ChannelMasker;
use PHPUnit\Framework\TestCase;

class ChannelMaskerTest extends TestCase
{
    public function test_masks_phone_and_email(): void
    {
        $masker = new ChannelMasker;

        $this->assertSame('+2557*****678', $masker->maskPhone('+255712345678'));
        $this->assertSame('s******@e******.com', $masker->maskEmail('student@example.com'));
        $this->assertNull($masker->maskPhone(null));
        $this->assertNull($masker->maskEmail('not-an-email'));
    }
}
