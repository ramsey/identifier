<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Service\Time;

use DateTimeImmutable;
use Ramsey\Identifier\Service\Time\StaticDateTimeService;
use Ramsey\Test\Identifier\TestCase;

class StaticDateTimeServiceTest extends TestCase
{
    public function testGetDateTime(): void
    {
        $dateTime = new DateTimeImmutable('2022-09-01T00:00:00+00:00');
        $service = new StaticDateTimeService($dateTime);

        $this->assertSame($dateTime, $service->getDateTime());
    }
}
