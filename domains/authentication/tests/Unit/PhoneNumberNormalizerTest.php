<?php

namespace Modules\Authentication\Tests\Unit;

use Modules\Authentication\Support\PhoneNumberNormalizer;
use PHPUnit\Framework\TestCase;

class PhoneNumberNormalizerTest extends TestCase
{
    public function test_normalizes_local_tanzania_mobile_to_e164(): void
    {
        $n = new PhoneNumberNormalizer;

        $this->assertSame('+255712345678', $n->normalize('0712 345 678'));
        $this->assertSame('+255712345678', $n->normalize('255712345678'));
        $this->assertSame('+255712345678', $n->normalize('+255712345678'));
        $this->assertTrue($n->isE164('+255712345678'));
        $this->assertFalse($n->isE164('0712345678'));
    }
}
