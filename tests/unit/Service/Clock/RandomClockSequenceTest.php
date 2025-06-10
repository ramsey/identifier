<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Service\Clock;

use Ramsey\Identifier\Service\Clock\RandomClockSequence;
use Ramsey\Test\Identifier\TestCase;

class RandomClockSequenceTest extends TestCase
{
    public function testValue(): void
    {
        $sequence = new RandomClockSequence();
        $current = $sequence->current();
        $next = $sequence->next();

        $this->assertNotSame($current, $next);
        $this->assertSame($next, $sequence->current());

        // Assert that it doesn't change unless next() is called.
        $this->assertSame($next, $sequence->current());
    }
}
