<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Service\BytesGenerator;

use PHPUnit\Framework\Attributes\TestWith;
use Ramsey\Identifier\Exception\InvalidArgument;
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

    #[TestWith([0])]
    #[TestWith([-1])]
    #[TestWith([-16])]
    public function testBytesThrowsExceptionForALengthLessThanOne(int $length): void
    {
        $bytesGenerator = new RandomBytesGenerator();

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('The length must be a positive integer; received ' . $length);

        // @phpstan-ignore argument.type (the generator must refuse a length its annotation forbids)
        $bytesGenerator->bytes($length);
    }

    public function testBytesAcceptsLengthOfOne(): void
    {
        $this->assertSame(1, strlen((new RandomBytesGenerator())->bytes(1)));
    }
}
