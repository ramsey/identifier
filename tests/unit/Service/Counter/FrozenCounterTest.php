<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Service\Counter;

use Ramsey\Identifier\Service\Counter\FrozenCounter;
use Ramsey\Test\Identifier\TestCase;

use const PHP_INT_MAX;

class FrozenCounterTest extends TestCase
{
    /**
     * @param int<0, max> $value
     *
     * @dataProvider clockSequenceProvider
     */
    public function testNext(int $value): void
    {
        $counter = new FrozenCounter($value);

        $this->assertSame($value, $counter->next());
    }

    /**
     * @return array<array{value: int<0, max>}>
     */
    public function clockSequenceProvider(): array
    {
        return [
            ['value' => 0],
            ['value' => 42],
            ['value' => 16383],
            ['value' => PHP_INT_MAX],
        ];
    }
}
