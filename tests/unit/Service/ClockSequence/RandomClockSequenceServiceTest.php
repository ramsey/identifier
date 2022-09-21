<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Service\ClockSequence;

use Ramsey\Identifier\Service\ClockSequence\RandomClockSequenceService;
use Ramsey\Test\Identifier\TestCase;

class RandomClockSequenceServiceTest extends TestCase
{
    public function testGetClockSequence(): void
    {
        $service = new RandomClockSequenceService();

        $this->assertIsInt($service->getClockSequence());
    }
}
