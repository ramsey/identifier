<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Service\Clock;

use DateTimeImmutable;
use Ramsey\Identifier\Service\Clock\RandomSequence;
use Ramsey\Test\Identifier\TestCase;

class RandomSequenceTest extends TestCase
{
    public function testValue(): void
    {
        $sequence = new RandomSequence();

        $this->assertIsInt($sequence->value('010000000000', new DateTimeImmutable()));
    }
}
