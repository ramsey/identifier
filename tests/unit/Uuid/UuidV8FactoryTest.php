<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Uuid;

use PHPUnit\Framework\Attributes\DataProvider;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Uuid\UuidV8Factory;
use Ramsey\Test\Identifier\TestCase;

class UuidV8FactoryTest extends TestCase
{
    private UuidV8Factory $factory;

    protected function setUp(): void
    {
        $this->factory = new UuidV8Factory();
    }

    public function testCreate(): void
    {
        $uuid = $this->factory->create("\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00");

        $this->assertSame('00000000-0000-8000-8000-000000000000', $uuid->toString());
    }

    public function testCreateWithMax(): void
    {
        $uuid = $this->factory->create("\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff");

        $this->assertSame('ffffffff-ffff-8fff-bfff-ffffffffffff', $uuid->toString());
    }

    public function testCreateWithoutBytesThrowsException(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('To create a version 8 UUID, the bytes must be a 16-byte octet string');

        $this->factory->create();
    }

    public function testCreateWithInvalidBytesThrowsException(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('To create a version 8 UUID, the bytes must be a 16-byte octet string');

        $this->factory->create('foobar');
    }

    public function testCreateFromBytes(): void
    {
        $uuid = $this->factory->createFromBytes("\xff\xff\xff\xff\xff\xff\x8f\xff\x8f\xff\xff\xff\xff\xff\xff\xff");

        $this->assertSame('ffffffff-ffff-8fff-8fff-ffffffffffff', $uuid->toString());
    }

    public function testCreateFromBytesThrowsException(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('The identifier must be a 16-byte octet string');

        $this->factory->createFromBytes("\xff\xff\xff\xff\xff\xff\x8f\xff\x8f\xff\xff\xff\xff\xff\xff");
    }

    public function testCreateFromBytesThrowsExceptionForNonVersion8Uuid(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage(
            "Invalid version 8 UUID: \"\xff\xff\xff\xff\xff\xff\x1f\xff\x8f\xff\xff\xff\xff\xff\xff\xff\"",
        );

        $this->factory->createFromBytes("\xff\xff\xff\xff\xff\xff\x1f\xff\x8f\xff\xff\xff\xff\xff\xff\xff");
    }

    public function testCreateFromIntegerWithMaxInteger(): void
    {
        $uuid = $this->factory->createFromInteger('340282366920937934553716840013076889599');

        $this->assertSame('ffffffff-ffff-8fff-bfff-ffffffffffff', $uuid->toString());
    }

    public function testCreateFromIntegerWithMinInteger(): void
    {
        $uuid = $this->factory->createFromInteger('604472133179351442128896');

        $this->assertSame('00000000-0000-8000-8000-000000000000', $uuid->toString());
    }

    public function testCreateFromIntegerThrowsExceptionForInvalidInteger(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Invalid integer: "foobar"');

        /** @phpstan-ignore-next-line */
        $this->factory->createFromInteger('foobar');
    }

    public function testCreateFromIntegerThrowsExceptionForNonVersion8Uuid(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Invalid version 8 UUID: 0');

        $this->factory->createFromInteger(0);
    }

    public function testCreateFromString(): void
    {
        $uuid = $this->factory->createFromString('ffffffff-ffff-8fff-8fff-ffffffffffff');

        $this->assertSame('ffffffff-ffff-8fff-8fff-ffffffffffff', $uuid->toString());
    }

    public function testCreateFromStringThrowsExceptionForWrongLength(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('The identifier must be a UUID in standard string representation');

        $this->factory->createFromString('ffffffff-ffff-8fff-8fff-fffffffffffff');
    }

    public function testCreateFromStringThrowsExceptionForWrongFormat(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('The identifier must be a UUID in standard string representation');

        $this->factory->createFromString('ffff-ffffffff-8fff-8fff-ffffffffffff');
    }

    public function testCreateFromHexadecimal(): void
    {
        $uuid = $this->factory->createFromHexadecimal('27433d43011d8a6a91611550863792c9');

        $this->assertSame('27433d43-011d-8a6a-9161-1550863792c9', $uuid->toString());
    }

    #[DataProvider('createFromHexadecimalThrowsExceptionProvider')]
    public function testCreateFromHexadecimalThrowsExceptionForInvalidHexadecimal(string $hexadecimal): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('The identifier must be a 32-character hexadecimal string');

        $this->factory->createFromHexadecimal($hexadecimal);
    }

    /**
     * @return array<string, array{hexadecimal: string}>
     */
    public static function createFromHexadecimalThrowsExceptionProvider(): array
    {
        return [
            'too short' => ['hexadecimal' => '27433d43011d8a6a91611550863792c'],
            'too long' => ['hexadecimal' => '27433d43011d8a6a91611550863792c9a'],
            'not hexadecimal' => ['hexadecimal' => '27433d43011d8a6a91611550863792cg'],
        ];
    }
}
