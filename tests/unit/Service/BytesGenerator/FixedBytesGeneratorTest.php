<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Service\BytesGenerator;

use PHPUnit\Framework\Attributes\DataProvider;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Service\BytesGenerator\FixedBytesGenerator;
use Ramsey\Test\Identifier\TestCase;

class FixedBytesGeneratorTest extends TestCase
{
    /**
     * @param non-empty-string $bytes
     * @param int<1, max> $length
     * @param non-empty-string $expectedBytes
     */
    #[DataProvider('bytesProvider')]
    public function testBytes(string $bytes, int $length, string $expectedBytes): void
    {
        $bytesGenerator = new FixedBytesGenerator($bytes);

        $this->assertSame($expectedBytes, $bytesGenerator->bytes($length));
    }

    /**
     * @return list<array{bytes: non-empty-string, length: int<1, max>, expectedBytes: non-empty-string}>
     */
    public static function bytesProvider(): array
    {
        return [
            [
                'bytes' => "\xab\xcd\xef\x01\x23\x45\x67\x89",
                'length' => 8,
                'expectedBytes' => "\xab\xcd\xef\x01\x23\x45\x67\x89",
            ],
            [
                'bytes' => "\x00\x11\x22\x33\x44\x55\x66\x77\x88\x99\xaa\xbb\xcc\xdd\xee\xff",
                'length' => 20,
                'expectedBytes' => "\x00\x11\x22\x33\x44\x55\x66\x77\x88\x99\xaa\xbb\xcc\xdd\xee\xff\x00\x11\x22\x33",
            ],
            [
                'bytes' => "\x00\x11\x22",
                'length' => 15,
                'expectedBytes' => "\x00\x11\x22\x00\x11\x22\x00\x11\x22\x00\x11\x22\x00\x11\x22",
            ],
            [
                'bytes' => "\x00\x11\x22\33",
                'length' => 17,
                'expectedBytes' => "\x00\x11\x22\33\x00\x11\x22\33\x00\x11\x22\33\x00\x11\x22\33\x00",
            ],
            [
                'bytes' => "\xff\xff\xff\xff\xab\xcd\xef\x01\x23\x45\x67\x89\xff\xff\xff\xff",
                'length' => 8,
                'expectedBytes' => "\xff\xff\xff\xff\xab\xcd\xef\x01",
            ],
        ];
    }

    public function testBytesMustBeNonEmptyString(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('The bytes must be a non-empty octet string');

        /** @phpstan-ignore argument.type */
        new FixedBytesGenerator('');
    }
}
