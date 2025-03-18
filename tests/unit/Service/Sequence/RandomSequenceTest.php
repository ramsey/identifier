<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Service\Sequence;

use Ramsey\Identifier\Service\Sequence\RandomSequence;
use Ramsey\Test\Identifier\TestCase;

class RandomSequenceTest extends TestCase
{
    public function testValue(): void
    {
        $sequence = new RandomSequence();

        $this->assertIsInt($sequence->next());
    }
}
