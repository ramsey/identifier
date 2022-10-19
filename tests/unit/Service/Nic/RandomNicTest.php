<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Service\Nic;

use Ramsey\Identifier\Service\Nic\RandomNic;
use Ramsey\Test\Identifier\TestCase;

use function hexdec;
use function strlen;
use function substr;

class RandomNicTest extends TestCase
{
    public function testAddress(): void
    {
        $nic = new RandomNic();
        $address = $nic->address();
        $firstOctet = substr($address, 0, 2);

        $this->assertSame(12, strlen($address));

        // Assert the multicast bit is set.
        $this->assertSame(1, hexdec($firstOctet) & 0x01);
    }
}
