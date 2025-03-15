<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Uuid;

use PHPUnit\Framework\Attributes\DataProvider;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Uuid\MicrosoftGuidFactory;
use Ramsey\Identifier\Uuid\UuidFactory;
use Ramsey\Identifier\Uuid\UuidV1;
use Ramsey\Identifier\Uuid\UuidV2;
use Ramsey\Identifier\Uuid\UuidV3;
use Ramsey\Identifier\Uuid\UuidV4;
use Ramsey\Identifier\Uuid\UuidV5;
use Ramsey\Identifier\Uuid\UuidV6;
use Ramsey\Identifier\Uuid\UuidV7;
use Ramsey\Identifier\Uuid\UuidV8;
use Ramsey\Identifier\Uuid\Variant;
use Ramsey\Identifier\Uuid\Version;
use Ramsey\Test\Identifier\TestCase;

class MicrosoftGuidFactoryTest extends TestCase
{
    private MicrosoftGuidFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new MicrosoftGuidFactory();
    }

    public function testCreate(): void
    {
        $guid = $this->factory->create();

        $this->assertSame(Variant::Microsoft, $guid->getVariant());
        $this->assertSame(Version::Random, $guid->getVersion());
    }

    public function testCreateFromBytes(): void
    {
        $guid = $this->factory->createFromBytes("\xff\xff\xff\xff\xff\xff\xff\x3f\xcf\xff\xff\xff\xff\xff\xff\xff");

        $this->assertSame('ffffffff-ffff-3fff-cfff-ffffffffffff', $guid->toString());
        $this->assertSame(Variant::Microsoft, $guid->getVariant());
        $this->assertSame(Version::NameMd5, $guid->getVersion());
    }

    public function testCreateFromBytesThrowsException(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Identifier must be a 16-byte string');

        $this->factory->createFromBytes("\xff\xff\xff\xff\xff\xff\xff\x4f\xcf\xff\xff\xff\xff\xff\xff");
    }

    public function testCreateFromBytesThrowsExceptionForInvalidGuid(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage(
            "Invalid Microsoft GUID: \"\xff\xff\xff\xff\xff\xff\xff\x4f\xef\xff\xff\xff\xff\xff\xff\xff\"",
        );

        $this->factory->createFromBytes("\xff\xff\xff\xff\xff\xff\xff\x4f\xef\xff\xff\xff\xff\xff\xff\xff");
    }

    public function testCreateFromIntegerWithMaxInteger(): void
    {
        $guid = $this->factory->createFromInteger('340282366920937934556022683022290583551');

        $this->assertSame('ffffffff-ffff-8fff-dfff-ffffffffffff', $guid->toString());
        $this->assertSame(Variant::Microsoft, $guid->getVariant());
        $this->assertSame(Version::Custom, $guid->getVersion());
    }

    public function testCreateFromIntegerWithMinInteger(): void
    {
        $guid = $this->factory->createFromInteger('75571698783969605582848');

        $this->assertSame('00000000-0000-1000-c000-000000000000', $guid->toString());
    }

    public function testCreateFromIntegerThrowsExceptionForInvalidInteger(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Invalid integer: "foobar"');

        /** @phpstan-ignore-next-line */
        $this->factory->createFromInteger('foobar');
    }

    public function testCreateFromIntegerThrowsExceptionForNonVersion4Uuid(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Invalid Microsoft GUID: 0');

        $this->factory->createFromInteger(0);
    }

    public function testCreateFromString(): void
    {
        $guid = $this->factory->createFromString('ffffffff-ffff-4fff-cfff-ffffffffffff');

        $this->assertSame('ffffffff-ffff-4fff-cfff-ffffffffffff', $guid->toString());
    }

    public function testCreateFromStringThrowsExceptionForWrongLength(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Identifier must be a UUID in string standard representation');

        $this->factory->createFromString('ffffffff-ffff-4fff-dfff-fffffffffffff');
    }

    public function testCreateFromStringThrowsExceptionForWrongFormat(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Identifier must be a UUID in string standard representation');

        $this->factory->createFromString('ffff-ffffffff-4fff-dfff-ffffffffffff');
    }

    #[DataProvider('createFromRfcProvider')]
    public function testCreateFromRfc(
        string $uuidValue,
        string $expectedGuidValue,
    ): void {
        $factory = new UuidFactory();
        $uuid = $factory->createFromString($uuidValue);

        /** @var UuidV1 | UuidV2 | UuidV3 | UuidV4 | UuidV5 | UuidV6 | UuidV7 | UuidV8 $typedUuid */
        $typedUuid = $uuid->toTypedUuid();

        $guid = $this->factory->createFromRfc($typedUuid);

        $this->assertSame($expectedGuidValue, $guid->toString());
        $this->assertFalse($guid->equals($uuid));
    }

    /**
     * @return array<string, array{uuidValue: string, expectedGuidValue: string}>
     */
    public static function createFromRfcProvider(): array
    {
        return [
            'v1 UUID to GUID' => [
                'uuidValue' => 'ffffffff-ffff-1fff-8fff-ffffffffffff',
                'expectedGuidValue' => 'ffffffff-ffff-1fff-cfff-ffffffffffff',
            ],
            'v2 UUID to GUID' => [
                'uuidValue' => 'ffffffff-ffff-2fff-8f00-ffffffffffff',
                'expectedGuidValue' => 'ffffffff-ffff-2fff-cf00-ffffffffffff',
            ],
            'v3 UUID to GUID' => [
                'uuidValue' => 'ffffffff-ffff-3fff-8fff-ffffffffffff',
                'expectedGuidValue' => 'ffffffff-ffff-3fff-cfff-ffffffffffff',
            ],
            'v4 UUID to GUID' => [
                'uuidValue' => 'ffffffff-ffff-4fff-8fff-ffffffffffff',
                'expectedGuidValue' => 'ffffffff-ffff-4fff-cfff-ffffffffffff',
            ],
            'v5 UUID to GUID' => [
                'uuidValue' => 'ffffffff-ffff-5fff-8fff-ffffffffffff',
                'expectedGuidValue' => 'ffffffff-ffff-5fff-cfff-ffffffffffff',
            ],
            'v6 UUID to GUID' => [
                'uuidValue' => 'ffffffff-ffff-6fff-8fff-ffffffffffff',
                'expectedGuidValue' => 'ffffffff-ffff-6fff-cfff-ffffffffffff',
            ],
            'v7 UUID to GUID' => [
                'uuidValue' => 'ffffffff-ffff-7fff-8fff-ffffffffffff',
                'expectedGuidValue' => 'ffffffff-ffff-7fff-cfff-ffffffffffff',
            ],
            'v8 UUID to GUID' => [
                'uuidValue' => 'ffffffff-ffff-8fff-8fff-ffffffffffff',
                'expectedGuidValue' => 'ffffffff-ffff-8fff-cfff-ffffffffffff',
            ],
        ];
    }

    public function testCreateFromHexadecimal(): void
    {
        $uuid = $this->factory->createFromHexadecimal('27433d43011d4a6ac1611550863792c9');

        $this->assertSame('27433d43-011d-4a6a-c161-1550863792c9', $uuid->toString());
    }

    #[DataProvider('createFromHexadecimalThrowsExceptionProvider')]
    public function testCreateFromHexadecimalThrowsExceptionForInvalidHexadecimal(string $hexadecimal): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Identifier must be a 32-character hexadecimal string');

        $this->factory->createFromHexadecimal($hexadecimal);
    }

    /**
     * @return array<string, array{hexadecimal: string}>
     */
    public static function createFromHexadecimalThrowsExceptionProvider(): array
    {
        return [
            'too short' => ['hexadecimal' => '27433d43011d4a6ac1611550863792c'],
            'too long' => ['hexadecimal' => '27433d43011d4a6ac1611550863792c9a'],
            'not hexadecimal' => ['hexadecimal' => '27433d43011d4a6ac1611550863792cg'],
        ];
    }
}
