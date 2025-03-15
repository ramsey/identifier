<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Uuid\Utility;

use PHPUnit\Framework\Attributes\DataProvider;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Uuid\Utility\Binary;
use Ramsey\Identifier\Uuid\Variant;
use Ramsey\Identifier\Uuid\Version;
use Ramsey\Test\Identifier\TestCase;

use function bin2hex;
use function sprintf;

class BinaryTest extends TestCase
{
    /**
     * @param non-empty-string $bytes
     */
    #[DataProvider('versionAndVariantBytesProvider')]
    public function testApplyVersionAndVariant(
        string $bytes,
        ?Version $version,
        Variant $variant,
        string $expectedBytes,
    ): void {
        $applied = (new Binary())->applyVersionAndVariant($bytes, $version, $variant);

        $this->assertSame(
            $expectedBytes,
            $applied,
            sprintf('Expected "%s", received "%s"', bin2hex($expectedBytes), bin2hex($applied)),
        );
    }

    /**
     * @return list<array{bytes: non-empty-string, version: Version | null, variant: Variant, expectedBytes: string}>
     */
    public static function versionAndVariantBytesProvider(): array
    {
        return [
            [
                'bytes' => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff",
                'version' => Version::GregorianTime,
                'variant' => Variant::Rfc4122,
                'expectedBytes' => "\xff\xff\xff\xff\xff\xff\x1f\xff\xbf\xff\xff\xff\xff\xff\xff\xff",
            ],
            [
                'bytes' => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff",
                'version' => Version::DceSecurity,
                'variant' => Variant::Rfc4122,
                'expectedBytes' => "\xff\xff\xff\xff\xff\xff\x2f\xff\xbf\xff\xff\xff\xff\xff\xff\xff",
            ],
            [
                'bytes' => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff",
                'version' => Version::HashMd5,
                'variant' => Variant::Rfc4122,
                'expectedBytes' => "\xff\xff\xff\xff\xff\xff\x3f\xff\xbf\xff\xff\xff\xff\xff\xff\xff",
            ],
            [
                'bytes' => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff",
                'version' => Version::Random,
                'variant' => Variant::Rfc4122,
                'expectedBytes' => "\xff\xff\xff\xff\xff\xff\x4f\xff\xbf\xff\xff\xff\xff\xff\xff\xff",
            ],
            [
                'bytes' => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff",
                'version' => Version::HashSha1,
                'variant' => Variant::Rfc4122,
                'expectedBytes' => "\xff\xff\xff\xff\xff\xff\x5f\xff\xbf\xff\xff\xff\xff\xff\xff\xff",
            ],
            [
                'bytes' => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff",
                'version' => Version::ReorderedGregorianTime,
                'variant' => Variant::Rfc4122,
                'expectedBytes' => "\xff\xff\xff\xff\xff\xff\x6f\xff\xbf\xff\xff\xff\xff\xff\xff\xff",
            ],
            [
                'bytes' => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff",
                'version' => Version::UnixTime,
                'variant' => Variant::Rfc4122,
                'expectedBytes' => "\xff\xff\xff\xff\xff\xff\x7f\xff\xbf\xff\xff\xff\xff\xff\xff\xff",
            ],
            [
                'bytes' => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff",
                'version' => Version::Custom,
                'variant' => Variant::Rfc4122,
                'expectedBytes' => "\xff\xff\xff\xff\xff\xff\x8f\xff\xbf\xff\xff\xff\xff\xff\xff\xff",
            ],
            [
                'bytes' => "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
                'version' => Version::Random,
                'variant' => Variant::Rfc4122,
                'expectedBytes' => "\x00\x00\x00\x00\x00\x00\x40\x00\x80\x00\x00\x00\x00\x00\x00\x00",
            ],
            [
                'bytes' => "\x11\x11\x11\x11\x11\x11\x11\x11\x11\x11\x11\x11\x11\x11\x11\x11",
                'version' => Version::Random,
                'variant' => Variant::Rfc4122,
                'expectedBytes' => "\x11\x11\x11\x11\x11\x11\x41\x11\x91\x11\x11\x11\x11\x11\x11\x11",
            ],
            [
                'bytes' => "\x22\x22\x22\x22\x22\x22\x22\x22\x22\x22\x22\x22\x22\x22\x22\x22",
                'version' => Version::Random,
                'variant' => Variant::Rfc4122,
                'expectedBytes' => "\x22\x22\x22\x22\x22\x22\x42\x22\xa2\x22\x22\x22\x22\x22\x22\x22",
            ],
            [
                'bytes' => "\x33\x33\x33\x33\x33\x33\x33\x33\x33\x33\x33\x33\x33\x33\x33\x33",
                'version' => Version::Random,
                'variant' => Variant::Rfc4122,
                'expectedBytes' => "\x33\x33\x33\x33\x33\x33\x43\x33\xb3\x33\x33\x33\x33\x33\x33\x33",
            ],
            [
                'bytes' => "\x44\x44\x44\x44\x44\x44\x44\x44\x44\x44\x44\x44\x44\x44\x44\x44",
                'version' => Version::Random,
                'variant' => Variant::Rfc4122,
                'expectedBytes' => "\x44\x44\x44\x44\x44\x44\x44\x44\x84\x44\x44\x44\x44\x44\x44\x44",
            ],
            [
                'bytes' => "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
                'version' => null,
                'variant' => Variant::Ncs,
                'expectedBytes' => "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
            ],
            [
                'bytes' => "\x11\x11\x11\x11\x11\x11\x11\x11\x11\x11\x11\x11\x11\x11\x11\x11",
                'version' => null,
                'variant' => Variant::Ncs,
                'expectedBytes' => "\x11\x11\x11\x11\x11\x11\x11\x11\x11\x11\x11\x11\x11\x11\x11\x11",
            ],
            [
                'bytes' => "\x88\x88\x88\x88\x88\x88\x88\x88\x88\x88\x88\x88\x88\x88\x88\x88",
                'version' => null,
                'variant' => Variant::Ncs,
                'expectedBytes' => "\x88\x88\x88\x88\x88\x88\x88\x88\x08\x88\x88\x88\x88\x88\x88\x88",
            ],
            [
                'bytes' => "\x99\x99\x99\x99\x99\x99\x99\x99\x99\x99\x99\x99\x99\x99\x99\x99",
                'version' => null,
                'variant' => Variant::Ncs,
                'expectedBytes' => "\x99\x99\x99\x99\x99\x99\x99\x99\x19\x99\x99\x99\x99\x99\x99\x99",
            ],
            [
                'bytes' => "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
                'version' => null,
                'variant' => Variant::ReservedMicrosoft,
                'expectedBytes' => "\x00\x00\x00\x00\x00\x00\x00\x00\xc0\x00\x00\x00\x00\x00\x00\x00",
            ],
            [
                'bytes' => "\x11\x11\x11\x11\x11\x11\x11\x11\x11\x11\x11\x11\x11\x11\x11\x11",
                'version' => null,
                'variant' => Variant::ReservedMicrosoft,
                'expectedBytes' => "\x11\x11\x11\x11\x11\x11\x11\x11\xd1\x11\x11\x11\x11\x11\x11\x11",
            ],
            [
                'bytes' => "\x88\x88\x88\x88\x88\x88\x88\x88\x88\x88\x88\x88\x88\x88\x88\x88",
                'version' => null,
                'variant' => Variant::ReservedMicrosoft,
                'expectedBytes' => "\x88\x88\x88\x88\x88\x88\x88\x88\xc8\x88\x88\x88\x88\x88\x88\x88",
            ],
            [
                'bytes' => "\x99\x99\x99\x99\x99\x99\x99\x99\x99\x99\x99\x99\x99\x99\x99\x99",
                'version' => null,
                'variant' => Variant::ReservedMicrosoft,
                'expectedBytes' => "\x99\x99\x99\x99\x99\x99\x99\x99\xd9\x99\x99\x99\x99\x99\x99\x99",
            ],
            [
                'bytes' => "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
                'version' => null,
                'variant' => Variant::ReservedFuture,
                'expectedBytes' => "\x00\x00\x00\x00\x00\x00\x00\x00\xe0\x00\x00\x00\x00\x00\x00\x00",
            ],
            [
                'bytes' => "\x11\x11\x11\x11\x11\x11\x11\x11\x11\x11\x11\x11\x11\x11\x11\x11",
                'version' => null,
                'variant' => Variant::ReservedFuture,
                'expectedBytes' => "\x11\x11\x11\x11\x11\x11\x11\x11\xf1\x11\x11\x11\x11\x11\x11\x11",
            ],
            [
                'bytes' => "\x88\x88\x88\x88\x88\x88\x88\x88\x88\x88\x88\x88\x88\x88\x88\x88",
                'version' => null,
                'variant' => Variant::ReservedFuture,
                'expectedBytes' => "\x88\x88\x88\x88\x88\x88\x88\x88\xe8\x88\x88\x88\x88\x88\x88\x88",
            ],
            [
                'bytes' => "\x99\x99\x99\x99\x99\x99\x99\x99\x99\x99\x99\x99\x99\x99\x99\x99",
                'version' => null,
                'variant' => Variant::ReservedFuture,
                'expectedBytes' => "\x99\x99\x99\x99\x99\x99\x99\x99\xf9\x99\x99\x99\x99\x99\x99\x99",
            ],
            [
                'bytes' => "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
                'version' => null,
                'variant' => Variant::Rfc4122,
                'expectedBytes' => "\x00\x00\x00\x00\x00\x00\x00\x00\x80\x00\x00\x00\x00\x00\x00\x00",
            ],
        ];
    }

    public function testApplyVersionAndVariantThrowsExceptionWhenBytesAreWrongLength(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('$bytes must be a a 16-byte string');

        (new Binary())->applyVersionAndVariant('foobar', null);
    }
}
