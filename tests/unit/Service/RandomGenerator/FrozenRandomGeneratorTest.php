<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Service\RandomGenerator;

use Ramsey\Identifier\Service\RandomGenerator\FrozenRandomGenerator;
use Ramsey\Test\Identifier\TestCase;

class FrozenRandomGeneratorTest extends TestCase
{
    public function testGetRandomBytesWithLengthExactlyAsValueProvided(): void
    {
        $bytes = "\xab\xcd\xef\x01\x23\x45\x67\x89";
        $randomGenerator = new FrozenRandomGenerator($bytes);

        $this->assertSame($bytes, $randomGenerator->bytes(8));
    }

    public function testGetRandomBytesWithLengthGreaterThanValueProvided(): void
    {
        $bytes = "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff";
        $randomGenerator = new FrozenRandomGenerator($bytes);

        $this->assertSame($bytes, $randomGenerator->bytes(20));
    }

    public function testGetRandomBytesWithLengthLessThanValueProvided(): void
    {
        $bytes = "\xff\xff\xff\xff\xab\xcd\xef\x01\x23\x45\x67\x89\xff\xff\xff\xff";
        $randomGenerator = new FrozenRandomGenerator($bytes);

        $this->assertSame("\xff\xff\xff\xff\xab\xcd\xef\x01", $randomGenerator->bytes(8));
    }
}
