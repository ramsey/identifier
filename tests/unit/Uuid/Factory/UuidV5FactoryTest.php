<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Uuid\Factory;

use Ramsey\Identifier\Exception\InvalidArgumentException;
use Ramsey\Identifier\Uuid\Factory\UuidV4Factory;
use Ramsey\Identifier\Uuid\Factory\UuidV5Factory;
use Ramsey\Identifier\Uuid\UuidV5;
use Ramsey\Test\Identifier\TestCase;

class UuidV5FactoryTest extends TestCase
{
    private UuidV5Factory $factory;

    protected function setUp(): void
    {
        $this->factory = new UuidV5Factory();
    }

    public function testCreate(): void
    {
        $namespace = (new UuidV4Factory())->create();

        $uuid1 = $this->factory->create($namespace, 'foo bar baz');
        $uuid2 = $this->factory->create($namespace, 'foo bar baz');

        // Two version 5 UUIDs created in the same namespace with the
        // same name must be equal.
        $this->assertInstanceOf(UuidV5::class, $uuid1);
        $this->assertInstanceOf(UuidV5::class, $uuid2);
        $this->assertTrue($uuid1->equals($uuid2));
    }

    public function testCreateWithoutNamespaceThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$namespace cannot be null when creating version 5 UUIDs');

        $this->factory->create();
    }

    public function testCreateWithoutNameThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$name cannot be null when creating version 5 UUIDs');

        $namespace = (new UuidV4Factory())->create();

        $this->factory->create($namespace);
    }

    public function testCreateFromBytes(): void
    {
        $uuid = $this->factory->createFromBytes("\xff\xff\xff\xff\xff\xff\x5f\xff\x8f\xff\xff\xff\xff\xff\xff\xff");

        $this->assertInstanceOf(UuidV5::class, $uuid);
        $this->assertSame('ffffffff-ffff-5fff-8fff-ffffffffffff', $uuid->toString());
    }

    public function testCreateFromBytesThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Identifier must be a 16-byte string');

        $this->factory->createFromBytes("\xff\xff\xff\xff\xff\xff\x5f\xff\x8f\xff\xff\xff\xff\xff\xff");
    }

    public function testCreateFromBytesThrowsExceptionForNonVersion5Uuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            "Invalid version 5 UUID: \"\xff\xff\xff\xff\xff\xff\x1f\xff\x8f\xff\xff\xff\xff\xff\xff\xff\"",
        );

        $this->factory->createFromBytes("\xff\xff\xff\xff\xff\xff\x1f\xff\x8f\xff\xff\xff\xff\xff\xff\xff");
    }

    public function testCreateFromHexadecimal(): void
    {
        $uuid = $this->factory->createFromHexadecimal('ffffffffffff5fff8fffffffffffffff');

        $this->assertInstanceOf(UuidV5::class, $uuid);
        $this->assertSame('ffffffff-ffff-5fff-8fff-ffffffffffff', $uuid->toString());
    }

    public function testCreateFromHexadecimalThrowsExceptionForWrongLength(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Identifier must be a 32-character hexadecimal string');

        $this->factory->createFromHexadecimal('ffffffffffff5fff8ffffffffffffffff');
    }

    public function testCreateFromHexadecimalThrowsExceptionForNonHexadecimal(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Identifier must be a 32-character hexadecimal string');

        $this->factory->createFromHexadecimal('ffffffffffff5fff8ffffffffffffffg');
    }

    public function testCreateFromHexadecimalThrowsExceptionForNonVersion5Uuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid version 5 UUID: "ffffffffffff1fff8fffffffffffffff"');

        $this->factory->createFromHexadecimal('ffffffffffff1fff8fffffffffffffff');
    }

    public function testCreateFromIntegerWithMaxInteger(): void
    {
        $uuid = $this->factory->createFromInteger('340282366920937707880125662270106632191');

        $this->assertInstanceOf(UuidV5::class, $uuid);
        $this->assertSame('ffffffff-ffff-5fff-bfff-ffffffffffff', $uuid->toString());
    }

    public function testCreateFromIntegerWithMinInteger(): void
    {
        $uuid = $this->factory->createFromInteger('377798542001608471871488');

        $this->assertInstanceOf(UuidV5::class, $uuid);
        $this->assertSame('00000000-0000-5000-8000-000000000000', $uuid->toString());
    }

    public function testCreateFromIntegerThrowsExceptionForInvalidInteger(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid integer: "foobar"');

        /** @phpstan-ignore-next-line */
        $this->factory->createFromInteger('foobar');
    }

    public function testCreateFromIntegerThrowsExceptionForNonVersion5Uuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid version 5 UUID: 0');

        $this->factory->createFromInteger(0);
    }

    public function testCreateFromString(): void
    {
        $uuid = $this->factory->createFromString('ffffffff-ffff-5fff-8fff-ffffffffffff');

        $this->assertInstanceOf(UuidV5::class, $uuid);
        $this->assertSame('ffffffff-ffff-5fff-8fff-ffffffffffff', $uuid->toString());
    }

    public function testCreateFromStringThrowsExceptionForWrongLength(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Identifier must be a UUID in string standard representation');

        $this->factory->createFromString('ffffffff-ffff-5fff-8fff-fffffffffffff');
    }

    public function testCreateFromStringThrowsExceptionForWrongFormat(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Identifier must be a UUID in string standard representation');

        $this->factory->createFromString('ffff-ffffffff-5fff-8fff-ffffffffffff');
    }
}
