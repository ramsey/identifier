<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Service\BytesGenerator;

use Ramsey\Identifier\Service\BytesGenerator\FixedBytesGenerator;
use Ramsey\Test\Identifier\TestCase;

class FixedBytesGeneratorTest extends TestCase
{
    public function testGetBytesWithLengthExactlyAsValueProvided(): void
    {
        $bytes = "\xab\xcd\xef\x01\x23\x45\x67\x89";
        $randomGenerator = new FixedBytesGenerator($bytes);

        $this->assertSame($bytes, $randomGenerator->bytes(8));
    }

    public function testGetBytesWithLengthGreaterThanValueProvided(): void
    {
        $bytes = "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff";
        $randomGenerator = new FixedBytesGenerator($bytes);

        $this->assertSame($bytes, $randomGenerator->bytes(20));
    }

    public function testGetBytesWithLengthLessThanValueProvided(): void
    {
        $bytes = "\xff\xff\xff\xff\xab\xcd\xef\x01\x23\x45\x67\x89\xff\xff\xff\xff";
        $randomGenerator = new FixedBytesGenerator($bytes);

        $this->assertSame("\xff\xff\xff\xff\xab\xcd\xef\x01", $randomGenerator->bytes(8));
    }
}
