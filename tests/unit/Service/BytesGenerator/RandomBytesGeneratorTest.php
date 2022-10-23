<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Service\BytesGenerator;

use Ramsey\Identifier\Service\BytesGenerator\RandomBytesGenerator;
use Ramsey\Test\Identifier\TestCase;

use function strlen;

class RandomBytesGeneratorTest extends TestCase
{
    public function testGetRandomBytes(): void
    {
        $bytesGenerator = new RandomBytesGenerator();

        $this->assertSame(16, strlen($bytesGenerator->bytes()));
    }
}
