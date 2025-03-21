<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Service\Clock;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Ramsey\Identifier\Service\Clock\Precision;
use Ramsey\Identifier\Service\Clock\StatefulSequence;
use Ramsey\Test\Identifier\TestCase;

use const PHP_INT_MAX;

class StatefulSequenceTest extends TestCase
{
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testValueIncreasesWhenNodeAndDateRemainTheSameWithMicrosecondPrecision(): void
    {
        $lastValue = 10;
        $node = '010000000000';
        $date = new DateTimeImmutable('2022-10-20 23:08:36.123456');
        $sequence = new StatefulSequence($lastValue, $node, $date, Precision::Microsecond);

        for ($i = 0; $i < 50; $i++) {
            $value = $sequence->value($node, $date);
            $this->assertSame($lastValue + 1, $value);
            $lastValue = $value;
        }
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testValueIncreasesWhenNodeAndDateRemainTheSameWithMillisecondPrecision(): void
    {
        $lastValue = 10;
        $node = '010000000000';
        $date = new DateTimeImmutable('2022-10-20 23:08:36.123');
        $sequence = new StatefulSequence($lastValue, $node, $date, Precision::Millisecond);

        for ($i = 0; $i < 50; $i++) {
            $value = $sequence->value($node, $date);
            $this->assertSame($lastValue + 1, $value);
            $lastValue = $value;
        }
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testValueRollsOverIfItReachesIntMax(): void
    {
        $node = '010000000000';
        $date = new DateTimeImmutable('2022-10-20 23:08:36.123456');
        $initialClockSeq = PHP_INT_MAX - 1;
        $sequence = new StatefulSequence($initialClockSeq, $node, $date);

        $this->assertSame(PHP_INT_MAX, $sequence->value($node, $date));
        $this->assertSame(0, $sequence->value($node, $date));
        $this->assertSame(1, $sequence->value($node, $date));
        $this->assertSame(2, $sequence->value($node, $date));
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testSequenceResetsWhenNodeChanges(): void
    {
        $node = 0;
        $lastValue = 10;
        $date = new DateTimeImmutable('2022-10-20 23:08:36.123456');
        $sequence = new StatefulSequence($lastValue, $node, $date);

        // Each time the node changes, the sequence value resets to a new random
        // value between 0 and PHP_INT_MAX. We will test that it is not the next
        // value incremented, though since the values are random, it's possible
        // to randomly select the next incremental value or even the same value,
        // so this test could result in false negatives.
        for ($i = $node + 1; $i < 11; $i++) {
            $value = $sequence->value($i, $date);
            $this->assertNotSame($lastValue + 1, $value);
            $lastValue = $value;
        }
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testSequenceResetsWhenDateChanges(): void
    {
        $node = '010000000000';
        $lastValue = 10;
        $microseconds = 111111;
        $date = new DateTimeImmutable('2022-10-20 23:08:36.' . $microseconds);
        $sequence = new StatefulSequence($lastValue, $node, $date);

        // Each time the date changes, the sequence value resets to a new random
        // value between 0 and PHP_INT_MAX. We will test that it is not the next
        // value incremented, though since the values are random, it's possible
        // to randomly select the next incremental value or even the same value,
        // so this test could result in false negatives.
        for ($i = 0; $i < 10; $i++) {
            $date = new DateTimeImmutable('2022-10-20 23:08:36.' . ++$microseconds);
            $value = $sequence->value($node, $date);
            $this->assertNotSame($lastValue + 1, $value);
            $lastValue = $value;
        }
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testValueIncreasesFromInitialSequenceWhenNodeAndDateNotOriginallyProvided(): void
    {
        $lastValue = 100;
        $node = '010000000000';
        $date = new DateTimeImmutable('2022-10-20 23:08:36.123456');
        $sequence = new StatefulSequence($lastValue);

        for ($i = 0; $i < 50; $i++) {
            $value = $sequence->value($node, $date);
            $this->assertSame($lastValue + 1, $value);
            $lastValue = $value;
        }
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testValueIncreasesFromInitialRandomSequenceWhenNoValuesOriginallyProvided(): void
    {
        $node = '010000000000';
        $date = new DateTimeImmutable('2022-10-20 23:08:36.123456');
        $sequence = new StatefulSequence();

        // Get our first value in the sequence.
        $lastValue = $sequence->value($node, $date);

        for ($i = 0; $i < 50; $i++) {
            $value = $sequence->value($node, $date);
            $this->assertSame($lastValue + 1, $value);
            $lastValue = $value;
        }
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testStateIsPreservedAcrossInstances(): void
    {
        $node = '010000000000';
        $date = new DateTimeImmutable('2022-10-20 23:08:36.123456');
        $sequence = new StatefulSequence();

        // Get our first value in the sequence.
        $lastValue = $sequence->value($node, $date);

        for ($i = 0; $i < 50; $i++) {
            // We'll set initial values to ensure these do not affect the global state of the sequence.
            $newSequence = new StatefulSequence(42 + $i, $i, new DateTimeImmutable());
            $value = $newSequence->value($node, $date);
            $this->assertSame($lastValue + 1, $value);
            $lastValue = $value;
        }
    }
}
