<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Service\Clock;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use Ramsey\Identifier\Service\Clock\FrozenSequence;
use Ramsey\Test\Identifier\TestCase;

use const PHP_INT_MAX;

class FrozenSequenceTest extends TestCase
{
    /**
     * @param int<0, max> $value
     */
    #[DataProvider('clockSequenceProvider')]
    public function testNext(int $value): void
    {
        $sequence = new FrozenSequence($value);

        $this->assertSame($value, $sequence->value('010000000000', new DateTimeImmutable()));
    }

    /**
     * @return list<array{value: int<0, max>}>
     */
    public static function clockSequenceProvider(): array
    {
        return [
            ['value' => 0],
            ['value' => 42],
            ['value' => 16383],
            ['value' => PHP_INT_MAX],
        ];
    }
}
