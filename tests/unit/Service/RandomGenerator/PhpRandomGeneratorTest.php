<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Service\RandomGenerator;

use Ramsey\Identifier\Service\RandomGenerator\PhpRandomGenerator;
use Ramsey\Test\Identifier\TestCase;

use function strlen;

class PhpRandomGeneratorTest extends TestCase
{
    public function testGetRandomBytes(): void
    {
        $randomGenerator = new PhpRandomGenerator();

        $this->assertSame(16, strlen($randomGenerator->bytes(16)));
    }
}
