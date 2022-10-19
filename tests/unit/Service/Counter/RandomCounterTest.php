<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Service\Counter;

use Ramsey\Identifier\Service\Counter\RandomCounter;
use Ramsey\Test\Identifier\TestCase;

class RandomCounterTest extends TestCase
{
    public function testNext(): void
    {
        $counter = new RandomCounter();

        $this->assertIsInt($counter->next());
    }
}
