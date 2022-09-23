<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Service\Node;

use Ramsey\Identifier\Service\Node\RandomNodeService;
use Ramsey\Test\Identifier\TestCase;

use function hexdec;
use function strlen;
use function substr;

class RandomNodeServiceTest extends TestCase
{
    public function testGetNode(): void
    {
        $service = new RandomNodeService();
        $node = $service->getNode();
        $firstOctet = substr($node, 0, 2);

        $this->assertSame(12, strlen($node));

        // Assert the multicast bit is set.
        $this->assertSame(1, hexdec($firstOctet) & 0x01);
    }
}
