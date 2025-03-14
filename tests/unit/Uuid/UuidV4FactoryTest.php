<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Uuid;

use PHPUnit\Framework\Attributes\DataProvider;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Uuid\UuidV4Factory;
use Ramsey\Test\Identifier\TestCase;

class UuidV4FactoryTest extends TestCase
{
    private UuidV4Factory $factory;

    protected function setUp(): void
    {
        $this->factory = new UuidV4Factory();
    }

    public function testCreateFromBytes(): void
    {
        $uuid = $this->factory->createFromBytes("\xff\xff\xff\xff\xff\xff\x4f\xff\x8f\xff\xff\xff\xff\xff\xff\xff");

        $this->assertSame('ffffffff-ffff-4fff-8fff-ffffffffffff', $uuid->toString());
    }

    public function testCreateFromBytesThrowsException(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Identifier must be a 16-byte string');

        $this->factory->createFromBytes("\xff\xff\xff\xff\xff\xff\x4f\xff\x8f\xff\xff\xff\xff\xff\xff");
    }

    public function testCreateFromBytesThrowsExceptionForNonVersion4Uuid(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage(
            "Invalid version 4 UUID: \"\xff\xff\xff\xff\xff\xff\x1f\xff\x8f\xff\xff\xff\xff\xff\xff\xff\"",
        );

        $this->factory->createFromBytes("\xff\xff\xff\xff\xff\xff\x1f\xff\x8f\xff\xff\xff\xff\xff\xff\xff");
    }

    public function testCreateFromIntegerWithMaxInteger(): void
    {
        $uuid = $this->factory->createFromInteger('340282366920937632322261936355783213055');

        $this->assertSame('ffffffff-ffff-4fff-bfff-ffffffffffff', $uuid->toString());
    }

    public function testCreateFromIntegerWithMinInteger(): void
    {
        $uuid = $this->factory->createFromInteger('302240678275694148452352');

        $this->assertSame('00000000-0000-4000-8000-000000000000', $uuid->toString());
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
        $this->expectExceptionMessage('Invalid version 4 UUID: 0');

        $this->factory->createFromInteger(0);
    }

    public function testCreateFromString(): void
    {
        $uuid = $this->factory->createFromString('ffffffff-ffff-4fff-8fff-ffffffffffff');

        $this->assertSame('ffffffff-ffff-4fff-8fff-ffffffffffff', $uuid->toString());
    }

    public function testCreateFromStringThrowsExceptionForWrongLength(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Identifier must be a UUID in string standard representation');

        $this->factory->createFromString('ffffffff-ffff-4fff-8fff-fffffffffffff');
    }

    public function testCreateFromStringThrowsExceptionForWrongFormat(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Identifier must be a UUID in string standard representation');

        $this->factory->createFromString('ffff-ffffffff-4fff-8fff-ffffffffffff');
    }

    public function testCreateFromHexadecimal(): void
    {
        $uuid = $this->factory->createFromHexadecimal('27433d43011d4a6a91611550863792c9');

        $this->assertSame('27433d43-011d-4a6a-9161-1550863792c9', $uuid->toString());
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
            'too short' => ['hexadecimal' => '27433d43011d4a6a91611550863792c'],
            'too long' => ['hexadecimal' => '27433d43011d4a6a91611550863792c9a'],
            'not hexadecimal' => ['hexadecimal' => '27433d43011d4a6a91611550863792cg'],
        ];
    }
}
