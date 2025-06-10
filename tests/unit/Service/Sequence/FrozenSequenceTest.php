<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Service\Sequence;

use PHPUnit\Framework\Attributes\DataProvider;
use Ramsey\Identifier\Service\Sequence\FrozenSequence;
use Ramsey\Test\Identifier\TestCase;

use function random_bytes;

use const PHP_INT_MAX;
use const PHP_INT_MIN;

class FrozenSequenceTest extends TestCase
{
    #[DataProvider('clockSequenceProvider')]
    public function testCurrent(int | string $value): void
    {
        $sequence = new FrozenSequence($value);

        $this->assertSame($value, $sequence->current());
    }

    #[DataProvider('clockSequenceProvider')]
    public function testNext(int | string $value): void
    {
        $sequence = new FrozenSequence($value);

        $this->assertSame($value, $sequence->next());
    }

    /**
     * @return array<string, array{value: int | string}>
     */
    public static function clockSequenceProvider(): array
    {
        return [
            'minimum system integer' => ['value' => PHP_INT_MIN],
            'zero' => ['value' => 0],
            'forty-two' => ['value' => 42],
            'maximum system integer' => ['value' => PHP_INT_MAX],
            'the letter A' => ['value' => 'A'],
            '16 random bytes' => ['value' => random_bytes(16)],
        ];
    }
}
