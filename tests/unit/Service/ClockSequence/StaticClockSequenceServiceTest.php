<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Service\ClockSequence;

use Ramsey\Identifier\Service\ClockSequence\StaticClockSequenceService;
use Ramsey\Test\Identifier\TestCase;

class StaticClockSequenceServiceTest extends TestCase
{
    /**
     * @param int<0, 16383> $value
     *
     * @dataProvider clockSequenceProvider
     */
    public function testGetClockSequence(int $value): void
    {
        $service = new StaticClockSequenceService($value);

        $this->assertSame($value, $service->getClockSequence());
    }

    /**
     * @return array<array{value: int<0, 16383>}>
     */
    public function clockSequenceProvider(): array
    {
        return [
            ['value' => 0],
            ['value' => 42],
            ['value' => 16383],
        ];
    }
}
