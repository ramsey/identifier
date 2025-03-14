<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Service\Clock;

use Ramsey\Identifier\Service\Clock\SystemClock;
use Ramsey\Test\Identifier\TestCase;

class SystemClockTest extends TestCase
{
    public function testNow(): void
    {
        $service = new SystemClock();
        $dateTime = $service->now();

        $this->assertNotSame($dateTime, $service->now());
    }
}
