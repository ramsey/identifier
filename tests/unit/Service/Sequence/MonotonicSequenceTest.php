<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Service\Sequence;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Ramsey\Identifier\Service\Sequence\MonotonicSequence;
use Ramsey\Identifier\Service\Sequence\SequenceOverflow;

use const PHP_INT_MAX;
use const PHP_INT_MIN;

class MonotonicSequenceTest extends TestCase
{
    #[DataProvider('sequenceProvider')]
    public function testSequenceWithState(int $value, int $step, int $expectedEnd): void
    {
        $state1 = 'state1';
        $state2 = 'state2';

        $sequence = new MonotonicSequence(start: $value, step: $step);

        for ($i = 0; $i < 100; $i++) {
            $this->assertSame($value, $sequence->current());
            $this->assertSame($value, $sequence->current($state1));
            $this->assertSame($value, $sequence->current($state2));

            $value += $step;

            $this->assertSame($value, $sequence->next());
            $this->assertSame($value, $sequence->next($state1));
            $this->assertSame($value, $sequence->next($state2));

            $this->assertSame($value, $sequence->current());
            $this->assertSame($value, $sequence->current($state1));
            $this->assertSame($value, $sequence->current($state2));
        }

        $this->assertSame($expectedEnd, $sequence->next());
        $this->assertSame($expectedEnd, $sequence->next($state1));
        $this->assertSame($expectedEnd, $sequence->next($state2));
    }

    /**
     * @return array<array{value: int, step: int, expectedEnd: int}>
     */
    public static function sequenceProvider(): array
    {
        return [
            ['value' => 0, 'step' => 1, 'expectedEnd' => 101],
            ['value' => -81, 'step' => 3, 'expectedEnd' => 222],
            ['value' => 0, 'step' => -1, 'expectedEnd' => -101],
            ['value' => 81, 'step' => -3, 'expectedEnd' => -222],

            // Using a step value of zero works (and is also considered monotonic).
            ['value' => 42, 'step' => 0, 'expectedEnd' => 42],
        ];
    }

    public function testSequenceOverflowWhenPreviousIsMaxInt(): void
    {
        $sequence = new MonotonicSequence(start: PHP_INT_MAX);

        $this->expectException(SequenceOverflow::class);
        $this->expectExceptionMessage('Unable to increase sequence beyond its maximum value');

        $sequence->next();
    }

    public function testSequenceOverflowWhenPreviousIsMinInt(): void
    {
        $sequence = new MonotonicSequence(start: PHP_INT_MIN, step: -1);

        $this->expectException(SequenceOverflow::class);
        $this->expectExceptionMessage('Unable to decrease sequence beyond its minimum value');

        $sequence->next();
    }

    public function testSequenceOverflowWhenStepIncreaseWillOverflow(): void
    {
        $sequence = new MonotonicSequence(start: PHP_INT_MAX - 27, step: 11);

        $this->assertSame(PHP_INT_MAX - 27 + 11, $sequence->next());
        $this->assertSame(PHP_INT_MAX - 27 + 11 + 11, $sequence->next());

        $this->expectException(SequenceOverflow::class);
        $this->expectExceptionMessage('Unable to increase sequence beyond its maximum value');

        $sequence->next();
    }

    public function testSequenceAlmostOverflowsMaximumButDoesNot(): void
    {
        $sequence = new MonotonicSequence(start: PHP_INT_MAX - 33, step: 11);

        $this->assertSame(PHP_INT_MAX - 33 + 11, $sequence->next());
        $this->assertSame(PHP_INT_MAX - 33 + 11 + 11, $sequence->next());

        // This should be exactly at the maximum.
        $this->assertSame(PHP_INT_MAX, $sequence->next());
    }

    public function testSequenceOverflowWhenStepDecreaseWillOverflow(): void
    {
        $sequence = new MonotonicSequence(start: PHP_INT_MIN + 27, step: -11);

        $this->assertSame(PHP_INT_MIN + 27 - 11, $sequence->next());
        $this->assertSame(PHP_INT_MIN + 27 - 11 - 11, $sequence->next());

        $this->expectException(SequenceOverflow::class);
        $this->expectExceptionMessage('Unable to decrease sequence beyond its minimum value');

        $sequence->next();
    }

    public function testSequenceAlmostOverflowsMinimumButDoesNot(): void
    {
        $sequence = new MonotonicSequence(start: PHP_INT_MIN + 33, step: -11);

        $this->assertSame(PHP_INT_MIN + 33 - 11, $sequence->next());
        $this->assertSame(PHP_INT_MIN + 33 - 11 - 11, $sequence->next());

        // This should be exactly at the minimum.
        $this->assertSame(PHP_INT_MIN, $sequence->next());
    }
}
