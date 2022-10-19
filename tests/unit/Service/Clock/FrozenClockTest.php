<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Service\Clock;

use DateTimeImmutable;
use Ramsey\Identifier\Service\Clock\FrozenClock;
use Ramsey\Test\Identifier\TestCase;

class FrozenClockTest extends TestCase
{
    public function testNow(): void
    {
        $dateTime = new DateTimeImmutable('2022-09-01T00:00:00+00:00');
        $service = new FrozenClock($dateTime);

        $this->assertSame($dateTime, $service->now());
    }
}
