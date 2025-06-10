<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Service\Clock;

use PHPUnit\Framework\Attributes\DataProvider;
use Ramsey\Identifier\Service\Clock\FrozenClockSequence;
use Ramsey\Test\Identifier\TestCase;

use const PHP_INT_MAX;

class FrozenClockSequenceTest extends TestCase
{
    /**
     * @param int<0, max> $value
     */
    #[DataProvider('clockSequenceProvider')]
    public function testCurrent(int $value): void
    {
        $sequence = new FrozenClockSequence($value);

        $this->assertSame($value, $sequence->current());
    }

    /**
     * @param int<0, max> $value
     */
    #[DataProvider('clockSequenceProvider')]
    public function testNext(int $value): void
    {
        $sequence = new FrozenClockSequence($value);

        $this->assertSame($value, $sequence->next());
    }

    /**
     * @return array<string, array{value: int<0, max>}>
     */
    public static function clockSequenceProvider(): array
    {
        return [
            'zero' => ['value' => 0],
            'forty-two' => ['value' => 42],
            'maximum system integer' => ['value' => PHP_INT_MAX],
        ];
    }
}
