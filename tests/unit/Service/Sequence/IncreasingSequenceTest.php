<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Service\Sequence;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Ramsey\Identifier\Service\Sequence\IncreasingSequence;
use Ramsey\Identifier\Service\Sequence\SequenceOverflow;

use const PHP_INT_MAX;

#[TestDox(IncreasingSequence::class)]
class IncreasingSequenceTest extends TestCase
{
    #[TestDox('it returns the next value in the sequence and increases the value by 1 each time')]
    public function testNext(): void
    {
        $value = 0;
        $sequence = new IncreasingSequence();

        for ($i = 0; $i < 100; $i++) {
            $this->assertSame(++$value, $sequence->next());
        }

        $this->assertSame(101, $sequence->next());
    }

    #[TestDox('it returns the next value in the sequence and increases the value by 3 each time')]
    public function testNextWithStep(): void
    {
        $value = -81;
        $step = 3;
        $sequence = new IncreasingSequence(start: $value, step: $step);

        for ($i = 0; $i < 100; $i++) {
            $value += $step;
            $this->assertSame($value, $sequence->next());
        }

        $this->assertSame(222, $sequence->next());
    }

    #[TestDox('it returns the next value in the sequence for the given state')]
    public function testNextWithDifferentStates(): void
    {
        $value = 100;
        $sequence = new IncreasingSequence(start: $value);

        $state1 = 'state1';
        $state2 = 'state2';
        $state3 = 'state3';

        for ($i = 0; $i < 100; $i++) {
            $value++;
            $this->assertSame($value, $sequence->next($state1));
            $this->assertSame($value, $sequence->next($state2));
            $this->assertSame($value, $sequence->next($state3));
        }

        $this->assertSame(201, $sequence->next($state1));
        $this->assertSame(201, $sequence->next($state2));
        $this->assertSame(201, $sequence->next($state3));
    }

    #[TestDox('it throws an exception if the step is not a positive integer')]
    public function testConstructorThrowsExceptionForInvalidStep(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Step must be a positive integer');

        /** @phpstan-ignore-next-line  */
        new IncreasingSequence(step: 0);
    }

    #[TestDox('it throws an exception if the sequence reaches the maximum value')]
    public function testSequenceOverflowWhenPreviousIsMaxInt(): void
    {
        $sequence = new IncreasingSequence(start: PHP_INT_MAX);

        $this->expectException(SequenceOverflow::class);
        $this->expectExceptionMessage('Unable to increase sequence beyond its maximum value');

        $sequence->next();
    }

    #[TestDox('it throws an exception if the step would cause the sequence to overflow the maximum value')]
    public function testSequenceOverflowWhenStepIncreaseWillOverflow(): void
    {
        // We'll use a weird step and start from a value that's off the step.
        $sequence = new IncreasingSequence(start: PHP_INT_MAX - 27, step: 11);

        $this->assertSame(PHP_INT_MAX - 27 + 11, $sequence->next());
        $this->assertSame(PHP_INT_MAX - 27 + 11 + 11, $sequence->next());

        $this->expectException(SequenceOverflow::class);
        $this->expectExceptionMessage('Unable to increase sequence beyond its maximum value');

        $sequence->next();
    }

    #[TestDox('it steps right up to the maximum value without throwing an exception')]
    public function testSequenceAlmostOverflowsButDoesNot(): void
    {
        // We'll use a weird step and start from a value that's off the step.
        $sequence = new IncreasingSequence(start: PHP_INT_MAX - 33, step: 11);

        $this->assertSame(PHP_INT_MAX - 33 + 11, $sequence->next());
        $this->assertSame(PHP_INT_MAX - 33 + 11 + 11, $sequence->next());

        // This should be exactly at the maximum.
        $this->assertSame(PHP_INT_MAX, $sequence->next());
    }
}
