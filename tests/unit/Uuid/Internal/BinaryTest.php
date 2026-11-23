<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Uuid\Internal;

use PHPUnit\Framework\Attributes\DataProvider;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Uuid;
use Ramsey\Identifier\Uuid\Internal\Binary;
use Ramsey\Identifier\Uuid\MicrosoftGuid;
use Ramsey\Identifier\Uuid\NonstandardUuid;
use Ramsey\Identifier\Uuid\UntypedUuid;
use Ramsey\Identifier\Uuid\UuidV4;
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
        $applied = Binary::applyVersionAndVariant($bytes, $version, $variant);

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
                'variant' => Variant::Rfc,
                'expectedBytes' => "\xff\xff\xff\xff\xff\xff\x1f\xff\xbf\xff\xff\xff\xff\xff\xff\xff",
            ],
            [
                'bytes' => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff",
                'version' => Version::DceSecurity,
                'variant' => Variant::Rfc,
                'expectedBytes' => "\xff\xff\xff\xff\xff\xff\x2f\xff\xbf\xff\xff\xff\xff\xff\xff\xff",
            ],
            [
                'bytes' => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff",
                'version' => Version::NameMd5,
                'variant' => Variant::Rfc,
                'expectedBytes' => "\xff\xff\xff\xff\xff\xff\x3f\xff\xbf\xff\xff\xff\xff\xff\xff\xff",
            ],
            [
                'bytes' => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff",
                'version' => Version::Random,
                'variant' => Variant::Rfc,
                'expectedBytes' => "\xff\xff\xff\xff\xff\xff\x4f\xff\xbf\xff\xff\xff\xff\xff\xff\xff",
            ],
            [
                'bytes' => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff",
                'version' => Version::NameSha1,
                'variant' => Variant::Rfc,
                'expectedBytes' => "\xff\xff\xff\xff\xff\xff\x5f\xff\xbf\xff\xff\xff\xff\xff\xff\xff",
            ],
            [
                'bytes' => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff",
                'version' => Version::ReorderedGregorianTime,
                'variant' => Variant::Rfc,
                'expectedBytes' => "\xff\xff\xff\xff\xff\xff\x6f\xff\xbf\xff\xff\xff\xff\xff\xff\xff",
            ],
            [
                'bytes' => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff",
                'version' => Version::UnixTime,
                'variant' => Variant::Rfc,
                'expectedBytes' => "\xff\xff\xff\xff\xff\xff\x7f\xff\xbf\xff\xff\xff\xff\xff\xff\xff",
            ],
            [
                'bytes' => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff",
                'version' => Version::Custom,
                'variant' => Variant::Rfc,
                'expectedBytes' => "\xff\xff\xff\xff\xff\xff\x8f\xff\xbf\xff\xff\xff\xff\xff\xff\xff",
            ],
            [
                'bytes' => "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
                'version' => Version::Random,
                'variant' => Variant::Rfc,
                'expectedBytes' => "\x00\x00\x00\x00\x00\x00\x40\x00\x80\x00\x00\x00\x00\x00\x00\x00",
            ],
            [
                'bytes' => "\x11\x11\x11\x11\x11\x11\x11\x11\x11\x11\x11\x11\x11\x11\x11\x11",
                'version' => Version::Random,
                'variant' => Variant::Rfc,
                'expectedBytes' => "\x11\x11\x11\x11\x11\x11\x41\x11\x91\x11\x11\x11\x11\x11\x11\x11",
            ],
            [
                'bytes' => "\x22\x22\x22\x22\x22\x22\x22\x22\x22\x22\x22\x22\x22\x22\x22\x22",
                'version' => Version::Random,
                'variant' => Variant::Rfc,
                'expectedBytes' => "\x22\x22\x22\x22\x22\x22\x42\x22\xa2\x22\x22\x22\x22\x22\x22\x22",
            ],
            [
                'bytes' => "\x33\x33\x33\x33\x33\x33\x33\x33\x33\x33\x33\x33\x33\x33\x33\x33",
                'version' => Version::Random,
                'variant' => Variant::Rfc,
                'expectedBytes' => "\x33\x33\x33\x33\x33\x33\x43\x33\xb3\x33\x33\x33\x33\x33\x33\x33",
            ],
            [
                'bytes' => "\x44\x44\x44\x44\x44\x44\x44\x44\x44\x44\x44\x44\x44\x44\x44\x44",
                'version' => Version::Random,
                'variant' => Variant::Rfc,
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
                'variant' => Variant::Microsoft,
                'expectedBytes' => "\x00\x00\x00\x00\x00\x00\x00\x00\xc0\x00\x00\x00\x00\x00\x00\x00",
            ],
            [
                'bytes' => "\x11\x11\x11\x11\x11\x11\x11\x11\x11\x11\x11\x11\x11\x11\x11\x11",
                'version' => null,
                'variant' => Variant::Microsoft,
                'expectedBytes' => "\x11\x11\x11\x11\x11\x11\x11\x11\xd1\x11\x11\x11\x11\x11\x11\x11",
            ],
            [
                'bytes' => "\x88\x88\x88\x88\x88\x88\x88\x88\x88\x88\x88\x88\x88\x88\x88\x88",
                'version' => null,
                'variant' => Variant::Microsoft,
                'expectedBytes' => "\x88\x88\x88\x88\x88\x88\x88\x88\xc8\x88\x88\x88\x88\x88\x88\x88",
            ],
            [
                'bytes' => "\x99\x99\x99\x99\x99\x99\x99\x99\x99\x99\x99\x99\x99\x99\x99\x99",
                'version' => null,
                'variant' => Variant::Microsoft,
                'expectedBytes' => "\x99\x99\x99\x99\x99\x99\x99\x99\xd9\x99\x99\x99\x99\x99\x99\x99",
            ],
            [
                'bytes' => "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
                'version' => null,
                'variant' => Variant::Future,
                'expectedBytes' => "\x00\x00\x00\x00\x00\x00\x00\x00\xe0\x00\x00\x00\x00\x00\x00\x00",
            ],
            [
                'bytes' => "\x11\x11\x11\x11\x11\x11\x11\x11\x11\x11\x11\x11\x11\x11\x11\x11",
                'version' => null,
                'variant' => Variant::Future,
                'expectedBytes' => "\x11\x11\x11\x11\x11\x11\x11\x11\xf1\x11\x11\x11\x11\x11\x11\x11",
            ],
            [
                'bytes' => "\x88\x88\x88\x88\x88\x88\x88\x88\x88\x88\x88\x88\x88\x88\x88\x88",
                'version' => null,
                'variant' => Variant::Future,
                'expectedBytes' => "\x88\x88\x88\x88\x88\x88\x88\x88\xe8\x88\x88\x88\x88\x88\x88\x88",
            ],
            [
                'bytes' => "\x99\x99\x99\x99\x99\x99\x99\x99\x99\x99\x99\x99\x99\x99\x99\x99",
                'version' => null,
                'variant' => Variant::Future,
                'expectedBytes' => "\x99\x99\x99\x99\x99\x99\x99\x99\xf9\x99\x99\x99\x99\x99\x99\x99",
            ],
            [
                'bytes' => "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
                'version' => null,
                'variant' => Variant::Rfc,
                'expectedBytes' => "\x00\x00\x00\x00\x00\x00\x00\x00\x80\x00\x00\x00\x00\x00\x00\x00",
            ],
        ];
    }

    public function testApplyVersionAndVariantThrowsExceptionWhenBytesAreWrongLength(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage(
            'When applying the version and variant bits, the bytes must be a 16-byte octet string',
        );

        Binary::applyVersionAndVariant('foobar', null);
    }

    #[DataProvider('networkBytesProvider')]
    public function testGetNetworkBytes(Uuid $uuid, string $expectedHexadecimal): void
    {
        $this->assertSame($expectedHexadecimal, bin2hex(Binary::getNetworkBytes($uuid)));
    }

    /**
     * @return list<array{uuid: Uuid, expectedHexadecimal: non-empty-string}>
     */
    public static function networkBytesProvider(): array
    {
        return [
            [
                'uuid' => new UuidV4("\x27\x43\x3d\x43\x01\x1d\x4a\x6a\x91\x61\x15\x50\x86\x37\x92\xc9"),
                'expectedHexadecimal' => '27433d43011d4a6a91611550863792c9',
            ],
            [
                'uuid' => new UuidV4('27433d43-011d-4a6a-9161-1550863792c9'),
                'expectedHexadecimal' => '27433d43011d4a6a91611550863792c9',
            ],
            [
                'uuid' => new MicrosoftGuid("\x43\x3d\x43\x27\x1d\x01\x6a\x4a\xc1\x61\x15\x50\x86\x37\x92\xc9"),
                'expectedHexadecimal' => '27433d43011d4a6ac1611550863792c9',
            ],
            [
                'uuid' => new MicrosoftGuid('27433d43-011d-4a6a-c161-1550863792c9'),
                'expectedHexadecimal' => '27433d43011d4a6ac1611550863792c9',
            ],
            [
                'uuid' => new MicrosoftGuid("\x43\x3d\x43\x27\x1d\x01\x6a\x4a\x91\x61\x15\x50\x86\x37\x92\xc9"),
                'expectedHexadecimal' => '27433d43011d4a6a91611550863792c9',
            ],
            [
                'uuid' => new UntypedUuid("\x27\x43\x3d\x43\x01\x1d\x4a\x6a\xc1\x61\x15\x50\x86\x37\x92\xc9"),
                'expectedHexadecimal' => '27433d43011d4a6ac1611550863792c9',
            ],
            [
                'uuid' => new UntypedUuid('27433d43-011d-4a6a-c161-1550863792c9'),
                'expectedHexadecimal' => '27433d43011d4a6ac1611550863792c9',
            ],
            [
                'uuid' => new NonstandardUuid("\x27\x43\x3d\x43\x01\x1d\x0a\x6a\xc1\x61\x15\x50\x86\x37\x92\xc9"),
                'expectedHexadecimal' => '27433d43011d0a6ac1611550863792c9',
            ],
        ];
    }

    public function testApplyVersionAndVariantForNcsKeepsTheOtherBits(): void
    {
        $bytes = "\x00\x11\x22\x33\x44\x55\x66\x77\x88\x99\xaa\xbb\xcc\xdd\xee\xff";

        $this->assertSame(
            '00112233445566770899aabbccddeeff',
            bin2hex(Binary::applyVersionAndVariant($bytes, null, Variant::Ncs)),
        );
    }

    public function testApplyVersionAndVariantForFutureKeepsTheOtherBits(): void
    {
        $bytes = "\x00\x11\x22\x33\x44\x55\x66\x77\x88\x99\xaa\xbb\xcc\xdd\xee\xff";

        $this->assertSame(
            '0011223344556677e899aabbccddeeff',
            bin2hex(Binary::applyVersionAndVariant($bytes, null, Variant::Future)),
        );
    }
}
