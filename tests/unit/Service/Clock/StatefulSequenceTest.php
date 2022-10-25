<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Service\Clock;

use DateTimeImmutable;
use Ramsey\Identifier\Service\Clock\StatefulSequence;
use Ramsey\Test\Identifier\TestCase;

use const PHP_INT_MAX;

class StatefulSequenceTest extends TestCase
{
    public function testValueRemainsTheSameAsDateIncreases(): void
    {
        $sequence = new StatefulSequence();
        $value = $sequence->value('010000000000', new DateTimeImmutable('2022-10-20 23:08:36.123456'));

        $this->assertSame(
            $value,
            $sequence->value('010000000000', new DateTimeImmutable('2022-10-20 23:08:36.123457')),
        );

        $this->assertSame(
            $value,
            $sequence->value('010000000000', new DateTimeImmutable('2022-10-20 23:08:36.123458')),
        );

        $this->assertSame(
            $value,
            $sequence->value('010000000000', new DateTimeImmutable('2022-10-20 23:08:36.123459')),
        );
    }

    public function testValueIncreasesIfDateRemainsTheSame(): void
    {
        $date = new DateTimeImmutable('2022-10-20 23:08:36.123456');

        $sequence = new StatefulSequence(10);

        $this->assertSame(11, $sequence->value('010000000000', $date));
        $this->assertSame(12, $sequence->value('010000000000', $date));
        $this->assertSame(13, $sequence->value('010000000000', $date));
        $this->assertSame(14, $sequence->value('010000000000', $date));
    }

    public function testValueRollsOverIfItReachesIntMax(): void
    {
        $date = new DateTimeImmutable('2022-10-20 23:08:36.123456');

        /** @var int<0, max> $initialClockSeq */
        $initialClockSeq = PHP_INT_MAX - 1;

        $sequence = new StatefulSequence($initialClockSeq);

        $this->assertSame(PHP_INT_MAX, $sequence->value('010000000000', $date));
        $this->assertSame(0, $sequence->value('010000000000', $date));
        $this->assertSame(1, $sequence->value('010000000000', $date));
        $this->assertSame(2, $sequence->value('010000000000', $date));
    }

    public function testValueChangesIfNodeChanges(): void
    {
        $sequence = new StatefulSequence();

        $value1 = $sequence->value('010000000000', new DateTimeImmutable('2022-10-20 23:08:36.123456'));
        $value2 = $sequence->value('010000000001', new DateTimeImmutable('2022-10-20 23:08:36.123457'));
        $value3 = $sequence->value('010000000001', new DateTimeImmutable('2022-10-20 23:08:36.123458'));
        $value4 = $sequence->value('010000000002', new DateTimeImmutable('2022-10-20 23:08:36.123459'));

        // Values 2 and 3 have the same node, so $value3 should === $value2.
        $this->assertSame($value2, $value3);

        $this->assertNotSame($value1, $value2);
        $this->assertNotSame($value3, $value4);
        $this->assertNotSame($value1, $value4);
    }
}
