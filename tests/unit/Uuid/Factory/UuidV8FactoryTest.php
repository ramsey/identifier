<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Uuid\Factory;

use Ramsey\Identifier\Exception\InvalidArgumentException;
use Ramsey\Identifier\Uuid\Factory\UuidV8Factory;
use Ramsey\Identifier\Uuid\UuidV8;
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
        $uuid = $this->factory->create('0', '0', '0');

        $this->assertInstanceOf(UuidV8::class, $uuid);
        $this->assertSame('00000000-0000-8000-8000-000000000000', $uuid->toString());
    }

    public function testCreateWithMax(): void
    {
        $uuid = $this->factory->create('ffffffffffff', 'fff', 'ffffffffffffffff');

        $this->assertInstanceOf(UuidV8::class, $uuid);
        $this->assertSame('ffffffff-ffff-8fff-bfff-ffffffffffff', $uuid->toString());
    }

    public function testCreateWithoutCustomFieldAThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$customFieldA cannot be null when creating version 8 UUIDs');

        $this->factory->create();
    }

    public function testCreateWithoutCustomFieldBThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$customFieldB cannot be null when creating version 8 UUIDs');

        $this->factory->create('000000000000');
    }

    public function testCreateWithoutCustomFieldCThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$customFieldC cannot be null when creating version 8 UUIDs');

        $this->factory->create('000000000000', '000');
    }

    public function testCreateWithInvalidCustomFieldAThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$customFieldA must be a 48-bit hexadecimal string');

        $this->factory->create('foobar', '0', '0');
    }

    public function testCreateWithInvalidCustomFieldBThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$customFieldB must be a 12-bit hexadecimal string');

        $this->factory->create('0', 'foobar', '0');
    }

    public function testCreateWithInvalidCustomFieldCThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$customFieldC must be a 62-bit hexadecimal string');

        $this->factory->create('0', '0', 'foobar');
    }

    public function testCreateFromBytes(): void
    {
        $uuid = $this->factory->createFromBytes("\xff\xff\xff\xff\xff\xff\x8f\xff\x8f\xff\xff\xff\xff\xff\xff\xff");

        $this->assertInstanceOf(UuidV8::class, $uuid);
        $this->assertSame('ffffffff-ffff-8fff-8fff-ffffffffffff', $uuid->toString());
    }

    public function testCreateFromBytesThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Identifier must be a 16-byte string');

        $this->factory->createFromBytes("\xff\xff\xff\xff\xff\xff\x8f\xff\x8f\xff\xff\xff\xff\xff\xff");
    }

    public function testCreateFromBytesThrowsExceptionForNonVersion8Uuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "Invalid version 8 UUID: \"\xff\xff\xff\xff\xff\xff\x1f\xff\x8f\xff\xff\xff\xff\xff\xff\xff\"",
        );

        $this->factory->createFromBytes("\xff\xff\xff\xff\xff\xff\x1f\xff\x8f\xff\xff\xff\xff\xff\xff\xff");
    }

    public function testCreateFromHexadecimal(): void
    {
        $uuid = $this->factory->createFromHexadecimal('ffffffffffff8fff8fffffffffffffff');

        $this->assertInstanceOf(UuidV8::class, $uuid);
        $this->assertSame('ffffffff-ffff-8fff-8fff-ffffffffffff', $uuid->toString());
    }

    public function testCreateFromHexadecimalThrowsExceptionForWrongLength(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Identifier must be a 32-character hexadecimal string');

        $this->factory->createFromHexadecimal('ffffffffffff8fff8ffffffffffffffff');
    }

    public function testCreateFromHexadecimalThrowsExceptionForNonHexadecimal(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Identifier must be a 32-character hexadecimal string');

        $this->factory->createFromHexadecimal('ffffffffffff8fff8ffffffffffffffg');
    }

    public function testCreateFromHexadecimalThrowsExceptionForNonVersion8Uuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid version 8 UUID: "ffffffffffff1fff8fffffffffffffff"');

        $this->factory->createFromHexadecimal('ffffffffffff1fff8fffffffffffffff');
    }

    public function testCreateFromIntegerWithMaxInteger(): void
    {
        $uuid = $this->factory->createFromInteger('340282366920937934553716840013076889599');

        $this->assertInstanceOf(UuidV8::class, $uuid);
        $this->assertSame('ffffffff-ffff-8fff-bfff-ffffffffffff', $uuid->toString());
    }

    public function testCreateFromIntegerWithMinInteger(): void
    {
        $uuid = $this->factory->createFromInteger('604472133179351442128896');

        $this->assertInstanceOf(UuidV8::class, $uuid);
        $this->assertSame('00000000-0000-8000-8000-000000000000', $uuid->toString());
    }

    public function testCreateFromIntegerThrowsExceptionForInvalidInteger(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid integer: "foobar"');

        /** @phpstan-ignore-next-line */
        $this->factory->createFromInteger('foobar');
    }

    public function testCreateFromIntegerThrowsExceptionForNonVersion8Uuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid version 8 UUID: 0');

        $this->factory->createFromInteger(0);
    }

    public function testCreateFromString(): void
    {
        $uuid = $this->factory->createFromString('ffffffff-ffff-8fff-8fff-ffffffffffff');

        $this->assertInstanceOf(UuidV8::class, $uuid);
        $this->assertSame('ffffffff-ffff-8fff-8fff-ffffffffffff', $uuid->toString());
    }

    public function testCreateFromStringThrowsExceptionForWrongLength(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Identifier must be a UUID in string standard representation');

        $this->factory->createFromString('ffffffff-ffff-8fff-8fff-fffffffffffff');
    }

    public function testCreateFromStringThrowsExceptionForWrongFormat(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Identifier must be a UUID in string standard representation');

        $this->factory->createFromString('ffff-ffffffff-8fff-8fff-ffffffffffff');
    }
}
