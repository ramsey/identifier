<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Service\Sequence;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;
use Ramsey\Identifier\Service\Cache\InMemoryCache;
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

            // Using a step value of zero works (and is also considered
            // monotonic).
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

    public function testNewSequenceDoesNotReadValueOfDestroyedSequence(): void
    {
        $cache = new InMemoryCache();

        // PHP may give each new object the handle of the one just destroyed.
        for ($i = 0; $i < 10; $i++) {
            $this->assertSame(1, (new MonotonicSequence(cache: $cache))->next());
        }
    }

    public function testLiveSequencesSharingCacheKeepSeparateValues(): void
    {
        $cache = new InMemoryCache();
        $first = new MonotonicSequence(cache: $cache);
        $second = new MonotonicSequence(cache: $cache);

        $this->assertSame(1, $first->next());
        $this->assertSame(2, $first->next());
        $this->assertSame(1, $second->next());
        $this->assertSame(2, $first->current());
    }

    public function testSequenceWithoutStateDoesNotUseCache(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->expects($this->never())->method($this->anything());

        $sequence = new MonotonicSequence(start: 5, step: 2, cache: $cache);

        $this->assertSame(5, $sequence->current());
        $this->assertSame(7, $sequence->next());
        $this->assertSame(7, $sequence->current());
    }

    public function testSequenceWithoutStateKeepsValueWhenCacheIsCleared(): void
    {
        $cache = new InMemoryCache();
        $sequence = new MonotonicSequence(cache: $cache);

        $sequence->next();
        $cache->clear();

        $this->assertSame(2, $sequence->next());
    }

    public function testCloneContinuesIndependentlyFromCurrentValue(): void
    {
        $original = new MonotonicSequence();
        $original->next();

        $clone = clone $original;

        $this->assertSame(2, $clone->next());
        $this->assertSame(1, $original->current());
    }

    public function testSequencesWithSameStateShareValueInCache(): void
    {
        $cache = new InMemoryCache();

        $this->assertSame(1, (new MonotonicSequence(cache: $cache))->next('state'));
        $this->assertSame(2, (new MonotonicSequence(cache: $cache))->next('state'));
    }

    #[TestWith([PHP_INT_MIN, 1])]
    #[TestWith([PHP_INT_MIN + 2, 5])]
    public function testRisingSequenceNearTheMinimumDoesNotOverflow(int $start, int $step): void
    {
        $sequence = new MonotonicSequence(start: $start, step: $step);

        $this->assertSame($start + $step, $sequence->next());
    }

    #[TestWith([PHP_INT_MAX, -1])]
    #[TestWith([PHP_INT_MAX - 2, -5])]
    public function testFallingSequenceNearTheMaximumDoesNotOverflow(int $start, int $step): void
    {
        $sequence = new MonotonicSequence(start: $start, step: $step);

        $this->assertSame($start + $step, $sequence->next());
    }

    #[TestWith([PHP_INT_MAX])]
    #[TestWith([PHP_INT_MIN])]
    public function testStepOfZeroAtTheLimitsDoesNotOverflow(int $start): void
    {
        $sequence = new MonotonicSequence(start: $start, step: 0);

        $this->assertSame($start, $sequence->next());
    }

    public function testStepOfTheMinimumIntegerReachesTheMinimumFromZero(): void
    {
        $sequence = new MonotonicSequence(start: 0, step: PHP_INT_MIN);

        $this->assertSame(PHP_INT_MIN, $sequence->next());
    }

    public function testStepOfTheMinimumIntegerOverflowsBelowZero(): void
    {
        $sequence = new MonotonicSequence(start: -1, step: PHP_INT_MIN);

        $this->expectException(SequenceOverflow::class);
        $this->expectExceptionMessage('Unable to decrease sequence beyond its minimum value');

        $sequence->next();
    }

    public function testNamedStateUsesAPrefixedCacheKey(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->expects($this->once())
            ->method('get')
            ->with('__ramsey_id_259414de|state', 0)
            ->willReturn(4);

        $this->assertSame(4, (new MonotonicSequence(cache: $cache))->current('state'));
    }
}
