<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Service\Time;

use DateTimeImmutable;
use Ramsey\Identifier\Service\Time\CurrentDateTimeService;
use Ramsey\Test\Identifier\TestCase;

class CurrentDateTimeServiceTest extends TestCase
{
    public function testGetDateTime(): void
    {
        $service = new CurrentDateTimeService();
        $dateTime = $service->getDateTime();

        $this->assertInstanceOf(DateTimeImmutable::class, $dateTime);
        $this->assertNotSame($dateTime, $service->getDateTime());
    }
}
