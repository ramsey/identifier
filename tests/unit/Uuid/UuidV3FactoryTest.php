<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Uuid;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestWith;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Uuid;
use Ramsey\Identifier\Uuid\MicrosoftGuid;
use Ramsey\Identifier\Uuid\UntypedUuid;
use Ramsey\Identifier\Uuid\UuidV3Factory;
use Ramsey\Identifier\Uuid\UuidV4Factory;
use Ramsey\Test\Identifier\TestCase;

class UuidV3FactoryTest extends TestCase
{
    private UuidV3Factory $factory;

    protected function setUp(): void
    {
        $this->factory = new UuidV3Factory();
    }

    public function testCreate(): void
    {
        $namespace = (new UuidV4Factory())->create();

        $uuid1 = $this->factory->create($namespace, 'foo bar baz');
        $uuid2 = $this->factory->create($namespace, 'foo bar baz');

        // Two version 3 UUIDs created in the same namespace with the
        // same name must be equal.
        $this->assertTrue($uuid1->equals($uuid2));
    }

    #[TestWith([new UntypedUuid('27433d43-011d-4a6a-c161-1550863792c9')])]
    #[TestWith([new UntypedUuid("\x27\x43\x3d\x43\x01\x1d\x4a\x6a\xc1\x61\x15\x50\x86\x37\x92\xc9")])]
    #[TestWith([new MicrosoftGuid('27433d43-011d-4a6a-c161-1550863792c9')])]
    #[TestWith([new MicrosoftGuid("\x43\x3d\x43\x27\x1d\x01\x6a\x4a\xc1\x61\x15\x50\x86\x37\x92\xc9")])]
    public function testCreateUsesTheValueOfTheNamespace(Uuid $namespace): void
    {
        $uuid = $this->factory->create($namespace, 'name');

        $this->assertSame('8d1a184d-75e3-3c6b-bec1-9bdacd54f8fc', $uuid->toString());
    }

    public function testCreateWithoutNamespaceThrowsException(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('When creating version 3 UUIDs, the namespace cannot be null');

        $this->factory->create();
    }

    public function testCreateWithoutNameThrowsException(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('When creating version 3 UUIDs, the name cannot be null');

        $namespace = (new UuidV4Factory())->create();

        $this->factory->create($namespace);
    }

    public function testCreateFromBytes(): void
    {
        $uuid = $this->factory->createFromBytes("\xff\xff\xff\xff\xff\xff\x3f\xff\x8f\xff\xff\xff\xff\xff\xff\xff");

        $this->assertSame('ffffffff-ffff-3fff-8fff-ffffffffffff', $uuid->toString());
    }

    public function testCreateFromBytesThrowsException(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('The identifier must be a 16-byte octet string');

        $this->factory->createFromBytes("\xff\xff\xff\xff\xff\xff\x3f\xff\x8f\xff\xff\xff\xff\xff\xff");
    }

    public function testCreateFromBytesThrowsExceptionForNonVersion3Uuid(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage(
            "Invalid version 3 UUID: \"\xff\xff\xff\xff\xff\xff\x1f\xff\x8f\xff\xff\xff\xff\xff\xff\xff\"",
        );

        $this->factory->createFromBytes("\xff\xff\xff\xff\xff\xff\x1f\xff\x8f\xff\xff\xff\xff\xff\xff\xff");
    }

    public function testCreateFromIntegerWithMaxInteger(): void
    {
        $uuid = $this->factory->createFromInteger('340282366920937556764398210441459793919');

        $this->assertSame('ffffffff-ffff-3fff-bfff-ffffffffffff', $uuid->toString());
    }

    public function testCreateFromIntegerWithMinInteger(): void
    {
        $uuid = $this->factory->createFromInteger('226682814549779825033216');

        $this->assertSame('00000000-0000-3000-8000-000000000000', $uuid->toString());
    }

    public function testCreateFromIntegerThrowsExceptionForInvalidInteger(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Invalid integer: "foobar"');

        /** @phpstan-ignore-next-line */
        $this->factory->createFromInteger('foobar');
    }

    public function testCreateFromIntegerThrowsExceptionForNonVersion3Uuid(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Invalid version 3 UUID: 0');

        $this->factory->createFromInteger(0);
    }

    public function testCreateFromString(): void
    {
        $uuid = $this->factory->createFromString('ffffffff-ffff-3fff-8fff-ffffffffffff');

        $this->assertSame('ffffffff-ffff-3fff-8fff-ffffffffffff', $uuid->toString());
    }

    public function testCreateFromStringThrowsExceptionForWrongLength(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('The identifier must be a UUID in standard string representation');

        $this->factory->createFromString('ffffffff-ffff-3fff-8fff-fffffffffffff');
    }

    public function testCreateFromStringThrowsExceptionForWrongFormat(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('The identifier must be a UUID in standard string representation');

        $this->factory->createFromString('ffff-ffffffff-3fff-8fff-ffffffffffff');
    }

    public function testCreateFromHexadecimal(): void
    {
        $uuid = $this->factory->createFromHexadecimal('27433d43011d3a6a91611550863792c9');

        $this->assertSame('27433d43-011d-3a6a-9161-1550863792c9', $uuid->toString());
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
            'too short' => ['hexadecimal' => '27433d43011d3a6a91611550863792c'],
            'too long' => ['hexadecimal' => '27433d43011d3a6a91611550863792c9a'],
            'not hexadecimal' => ['hexadecimal' => '27433d43011d3a6a91611550863792cg'],
        ];
    }
}
