<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Uuid\Internal;

use PHPUnit\Framework\Attributes\DataProvider;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Uuid\UuidV1;
use Ramsey\Identifier\Uuid\UuidV3;
use Ramsey\Identifier\Uuid\UuidV4;
use Ramsey\Identifier\Uuid\UuidV5;
use Ramsey\Identifier\Uuid\UuidV6;
use Ramsey\Identifier\Uuid\UuidV7;
use Ramsey\Identifier\Uuid\UuidV8;
use Ramsey\Identifier\Uuid\Variant;
use Ramsey\Identifier\Uuid\Version;
use Ramsey\Test\Identifier\TestCase;

use function hex2bin;
use function json_encode;
use function serialize;
use function sprintf;
use function str_repeat;
use function unserialize;

class FromRawBytesTest extends TestCase
{
    private const string ZEROS = "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00";
    private const string ONES = "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff";
    private const string VERSION_4 = "\x27\x43\x3d\x43\x01\x1d\x4a\x6a\x91\x61\x15\x50\x86\x37\x92\xc9";

    private const array CLASSES = [
        UuidV1::class => Version::GregorianTime,
        UuidV3::class => Version::NameMd5,
        UuidV4::class => Version::Random,
        UuidV5::class => Version::NameSha1,
        UuidV6::class => Version::ReorderedGregorianTime,
        UuidV7::class => Version::UnixTime,
        UuidV8::class => Version::Custom,
    ];

    /**
     * @param class-string<UuidV1 | UuidV3 | UuidV4 | UuidV5 | UuidV6 | UuidV7 | UuidV8> $class
     */
    #[DataProvider('wrongLengthProvider')]
    public function testFromRawBytesThrowsForAStringThatIsNot16BytesLong(string $class, string $bytes): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage(
            'When applying the version and variant bits, the bytes must be a 16-byte octet string',
        );

        $class::fromRawBytes($bytes);
    }

    /**
     * @return array<string, array{class: class-string, bytes: string}>
     */
    public static function wrongLengthProvider(): array
    {
        $cases = [];

        foreach (self::CLASSES as $class => $version) {
            foreach ([0, 15, 17] as $length) {
                $cases[sprintf('version %d, %d bytes', $version->value, $length)] = [
                    'class' => $class,
                    'bytes' => str_repeat("\xff", $length),
                ];
            }
        }

        return $cases;
    }

    /**
     * @param class-string<UuidV1 | UuidV3 | UuidV4 | UuidV5 | UuidV6 | UuidV7 | UuidV8> $class
     * @param non-empty-string $expectedHex
     */
    #[DataProvider('bytesProvider')]
    public function testFromRawBytesSetsTheVersionAndTheVariant(
        string $class,
        Version $version,
        string $bytes,
        string $expectedHex,
    ): void {
        $uuid = $class::fromRawBytes($bytes);

        $this->assertSame($class, $uuid::class);
        $this->assertSame($version, $uuid->getVersion());
        $this->assertSame(Variant::Rfc, $uuid->getVariant());
        $this->assertSame($expectedHex, $uuid->toHexadecimal());
        $this->assertSame(hex2bin($expectedHex), $uuid->toBytes());
    }

    /**
     * @param class-string<UuidV1 | UuidV3 | UuidV4 | UuidV5 | UuidV6 | UuidV7 | UuidV8> $class
     * @param non-empty-string $expectedHex
     */
    #[DataProvider('bytesProvider')]
    public function testFromRawBytesCreatesTheUuidTheConstructorCreates(
        string $class,
        Version $version,
        string $bytes,
        string $expectedHex,
    ): void {
        $uuid = $class::fromRawBytes($bytes);
        $expected = new $class($expectedHex);

        $this->assertTrue($uuid->equals($expected));
        $this->assertSame(0, $uuid->compareTo($expected));
        $this->assertSame($expected->toString(), $uuid->toString());
        $this->assertSame($expected->toInteger(), $uuid->toInteger());
        $this->assertSame($expected->toUrn(), $uuid->toUrn());
        $this->assertSame(json_encode($expected), json_encode($uuid));
    }

    /**
     * @param class-string<UuidV1 | UuidV3 | UuidV4 | UuidV5 | UuidV6 | UuidV7 | UuidV8> $class
     * @param non-empty-string $expectedHex
     */
    #[DataProvider('bytesProvider')]
    public function testAUuidFromRawBytesIsTheSameAfterSerialization(
        string $class,
        Version $version,
        string $bytes,
        string $expectedHex,
    ): void {
        $uuid = $class::fromRawBytes($bytes);
        $unserialized = unserialize(serialize($uuid));

        $this->assertInstanceOf($class, $unserialized);
        $this->assertSame($expectedHex, $unserialized->toHexadecimal());
        $this->assertTrue($uuid->equals($unserialized));
    }

    /**
     * @return array<string, array{class: class-string, version: Version, bytes: string, expectedHex: string}>
     */
    public static function bytesProvider(): array
    {
        $cases = [];

        foreach (self::CLASSES as $class => $version) {
            $cases[sprintf('version %d, zeros', $version->value)] = [
                'class' => $class,
                'version' => $version,
                'bytes' => self::ZEROS,
                'expectedHex' => sprintf('000000000000%x0008000000000000000', $version->value),
            ];

            $cases[sprintf('version %d, ones', $version->value)] = [
                'class' => $class,
                'version' => $version,
                'bytes' => self::ONES,
                'expectedHex' => sprintf('ffffffffffff%xfffbfffffffffffffff', $version->value),
            ];

            $cases[sprintf('version %d, the bytes of a version 4 UUID', $version->value)] = [
                'class' => $class,
                'version' => $version,
                'bytes' => self::VERSION_4,
                'expectedHex' => sprintf('27433d43011d%xa6a91611550863792c9', $version->value),
            ];
        }

        return $cases;
    }

    public function testEachClassCreatesObjectsOfItsOwnClass(): void
    {
        $first = UuidV4::fromRawBytes(self::VERSION_4);
        $second = UuidV7::fromRawBytes(self::VERSION_4);
        $third = UuidV4::fromRawBytes(self::VERSION_4);

        $this->assertSame(Version::Random, $first->getVersion());
        $this->assertSame('27433d43011d4a6a91611550863792c9', $first->toHexadecimal());
        $this->assertSame(Version::UnixTime, $second->getVersion());
        $this->assertSame('27433d43011d7a6a91611550863792c9', $second->toHexadecimal());
        $this->assertSame(Version::Random, $third->getVersion());
        $this->assertSame('27433d43011d4a6a91611550863792c9', $third->toHexadecimal());
    }
}
