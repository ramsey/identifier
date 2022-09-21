<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Service\Random;

use Ramsey\Identifier\Service\Random\RandomBytesService;
use Ramsey\Test\Identifier\TestCase;

use function strlen;

class RandomBytesServiceTest extends TestCase
{
    public function testGetRandomBytes(): void
    {
        $service = new RandomBytesService();

        $this->assertSame(16, strlen($service->getRandomBytes(16)));
    }
}
