<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier;

use DateTimeImmutable;
use Identifier\Uuid\UuidInterface;
use Ramsey\Identifier\Exception\InvalidArgumentException;
use Ramsey\Identifier\Uuid;
use Ramsey\Identifier\Uuid\Dce\Domain;
use Ramsey\Identifier\Uuid\MaxUuid;
use Ramsey\Identifier\Uuid\NilUuid;
use Ramsey\Identifier\Uuid\NonstandardUuid;
use Ramsey\Identifier\Uuid\UuidV1;
use Ramsey\Identifier\Uuid\UuidV2;
use Ramsey\Identifier\Uuid\UuidV3;
use Ramsey\Identifier\Uuid\UuidV4;
use Ramsey\Identifier\Uuid\UuidV5;
use Ramsey\Identifier\Uuid\UuidV6;
use Ramsey\Identifier\Uuid\UuidV7;
use Ramsey\Identifier\Uuid\UuidV8;

use function sprintf;
use function substr;

use const PHP_INT_MAX;

class UuidTest extends TestCase
{
    /**
     * @param class-string<UuidInterface> $expectedType
     *
     * @dataProvider fromBytesProvider
     */
    public function testFromBytes(string $bytes, string $expectedType): void
    {
        $this->assertInstanceOf($expectedType, Uuid::fromBytes($bytes));
    }

    /**
     * @return array<array{bytes: non-empty-string, expectedType: class-string<UuidInterface>}>
     */
    public function fromBytesProvider(): array
    {
        return [
            [
                'bytes' => "\0\0\0\0\0\0\x10\0\xa0\0\0\0\0\0\0\0",
                'expectedType' => UuidV1::class,
            ],
            [
                'bytes' => "\0\0\0\0\0\0\x20\0\xa0\x02\0\0\0\0\0\0",
                'expectedType' => UuidV2::class,
            ],
            [
                'bytes' => "\0\0\0\0\0\0\x30\0\xa0\0\0\0\0\0\0\0",
                'expectedType' => UuidV3::class,
            ],
            [
                'bytes' => "\0\0\0\0\0\0\x40\0\xa0\0\0\0\0\0\0\0",
                'expectedType' => UuidV4::class,
            ],
            [
                'bytes' => "\0\0\0\0\0\0\x50\0\xa0\0\0\0\0\0\0\0",
                'expectedType' => UuidV5::class,
            ],
            [
                'bytes' => "\0\0\0\0\0\0\x60\0\xa0\0\0\0\0\0\0\0",
                'expectedType' => UuidV6::class,
            ],
            [
                'bytes' => "\0\0\0\0\0\0\x70\0\xa0\0\0\0\0\0\0\0",
                'expectedType' => UuidV7::class,
            ],
            [
                'bytes' => "\0\0\0\0\0\0\x80\0\xa0\0\0\0\0\0\0\0",
                'expectedType' => UuidV8::class,
            ],
            [
                'bytes' => "\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0",
                'expectedType' => NilUuid::class,
            ],
            [
                'bytes' => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff",
                'expectedType' => MaxUuid::class,
            ],
            [
                'bytes' => "\0\0\0\0\0\0\0\0\xc0\0\0\0\0\0\0\0",
                'expectedType' => NonstandardUuid::class,
            ],
        ];
    }

    /**
     * @dataProvider fromBytesInvalidInputProvider
     */
    public function testFromBytesThrowsExceptionForInvalidInput(string $input): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Identifier must be a 16-byte string');

        Uuid::fromBytes($input);
    }

    /**
     * @return array<array{input: string}>
     */
    public function fromBytesInvalidInputProvider(): array
    {
        return [
            ['input' => "\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0"],
            ['input' => "\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0"],
            ['input' => '00000000-0000-1000-a000-000000000000'],
            ['input' => '0000000000001000a000000000000000'],
            ['input' => '123.456'],
            ['input' => '340282366920937405648670758612812955647'],
        ];
    }

    /**
     * @param class-string<UuidInterface> $expectedType
     *
     * @dataProvider fromHexadecimalProvider
     */
    public function testFromHexadecimal(string $hexadecimal, string $expectedType): void
    {
        $this->assertInstanceOf($expectedType, Uuid::fromHexadecimal($hexadecimal));
    }

    /**
     * @return array<array{hexadecimal: non-empty-string, expectedType: class-string<UuidInterface>}>
     */
    public function fromHexadecimalProvider(): array
    {
        return [
            [
                'hexadecimal' => '0000000000001000a000000000000000',
                'expectedType' => UuidV1::class,
            ],
            [
                'hexadecimal' => '0000000000002000a002000000000000',
                'expectedType' => UuidV2::class,
            ],
            [
                'hexadecimal' => '0000000000003000a000000000000000',
                'expectedType' => UuidV3::class,
            ],
            [
                'hexadecimal' => '0000000000004000a000000000000000',
                'expectedType' => UuidV4::class,
            ],
            [
                'hexadecimal' => '0000000000005000a000000000000000',
                'expectedType' => UuidV5::class,
            ],
            [
                'hexadecimal' => '0000000000006000a000000000000000',
                'expectedType' => UuidV6::class,
            ],
            [
                'hexadecimal' => '0000000000007000a000000000000000',
                'expectedType' => UuidV7::class,
            ],
            [
                'hexadecimal' => '0000000000008000a000000000000000',
                'expectedType' => UuidV8::class,
            ],
            [
                'hexadecimal' => '00000000000000000000000000000000',
                'expectedType' => NilUuid::class,
            ],
            [
                'hexadecimal' => 'ffffffffffffffffffffffffffffffff',
                'expectedType' => MaxUuid::class,
            ],
            [
                'hexadecimal' => '0000000000000000c000000000000000',
                'expectedType' => NonstandardUuid::class,
            ],
        ];
    }

    /**
     * @dataProvider fromHexadecimalInvalidInputProvider
     */
    public function testFromHexadecimalThrowsExceptionForInvalidInput(string $input): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Identifier must be a 32-character hexadecimal string');

        Uuid::fromHexadecimal($input);
    }

    /**
     * @return array<array{input: string}>
     */
    public function fromHexadecimalInvalidInputProvider(): array
    {
        return [
            ['input' => "\0\0\0\0\0\0\x10\0\xa0\0\0\0\0\0\0\0"],
            ['input' => '00000000-0000-1000-a000-000000000000'],
            ['input' => '123.456'],
            ['input' => '340282366920937405648670758612812955647'],
            ['input' => 'fffffffffffffffffffffffffffffffff'],
            ['input' => 'fffffffffffffffffffffffffffffffg'],
        ];
    }

    public function testFromIntegerThrowsExceptionForNegativeNativeInteger(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to create a UUID from a negative integer');

        Uuid::fromInteger(-1);
    }

    public function testFromIntegerThrowsExceptionForNegativeStringInteger(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to create a UUID from a negative integer');

        Uuid::fromInteger('-9223372036854775809');
    }

    /**
     * @dataProvider fromIntegerInvalidIntegerProvider
     */
    public function testFromIntegerThrowsExceptionForInvalidInteger(int | string $input): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('Invalid integer: "%s"', $input));

        /** @phpstan-ignore-next-line */
        Uuid::fromInteger($input);
    }

    /**
     * @return array<array{input: int | string}>
     */
    public function fromIntegerInvalidIntegerProvider(): array
    {
        return [
            ['input' => "\0\0\0\0\0\0\x10\0\xa0\0\0\0\0\0\0\0"],
            ['input' => '00000000-0000-1000-a000-000000000000'],
            ['input' => '0000000000001000a000000000000000'],
            ['input' => '123.456'],
        ];
    }

    /**
     * @param int | numeric-string $value
     * @param class-string<UuidInterface> $expectedType
     *
     * @dataProvider fromIntegerProvider
     */
    public function testFromInteger(int | string $value, string $expectedType): void
    {
        $uuid = Uuid::fromInteger($value);

        $this->assertInstanceOf($expectedType, $uuid);
    }

    /**
     * @return array<array{value: int | numeric-string, expectedType: class-string<UuidInterface>}>
     */
    public function fromIntegerProvider(): array
    {
        return [
            [
                'value' => '340282366920937405648670758612812955647',
                'expectedType' => UuidV1::class,
            ],
            [
                'value' => '340282366920937481206463271358028578815',
                'expectedType' => UuidV2::class,
            ],
            [
                'value' => '340282366920937556764398210441459793919',
                'expectedType' => UuidV3::class,
            ],
            [
                'value' => '340282366920937632322261936355783213055',
                'expectedType' => UuidV4::class,
            ],
            [
                'value' => '340282366920937707880125662270106632191',
                'expectedType' => UuidV5::class,
            ],
            [
                'value' => '340282366920937783437989388184430051327',
                'expectedType' => UuidV6::class,
            ],
            [
                'value' => '340282366920937858995853114098753470463',
                'expectedType' => UuidV7::class,
            ],
            [
                'value' => '340282366920937934553716840013076889599',
                'expectedType' => UuidV8::class,
            ],
            [
                'value' => '0',
                'expectedType' => NilUuid::class,
            ],
            [
                'value' => 0,
                'expectedType' => NilUuid::class,
            ],
            [
                'value' => '340282366920938463463374607431768211455',
                'expectedType' => MaxUuid::class,
            ],
            [
                'value' => PHP_INT_MAX,
                'expectedType' => NonstandardUuid::class,
            ],
            [
                'value' => (string) PHP_INT_MAX,
                'expectedType' => NonstandardUuid::class,
            ],
        ];
    }

    /**
     * @param class-string<UuidInterface> $expectedType
     *
     * @dataProvider fromStringProvider
     */
    public function testFromString(string $value, string $expectedType): void
    {
        $this->assertInstanceOf($expectedType, Uuid::fromString($value));
    }

    /**
     * @return array<array{value: non-empty-string, expectedType: class-string<UuidInterface>}>
     */
    public function fromStringProvider(): array
    {
        return [
            [
                'value' => '00000000-0000-1000-a000-000000000000',
                'expectedType' => UuidV1::class,
            ],
            [
                'value' => '00000000-0000-2000-a002-000000000000',
                'expectedType' => UuidV2::class,
            ],
            [
                'value' => '00000000-0000-3000-a000-000000000000',
                'expectedType' => UuidV3::class,
            ],
            [
                'value' => '00000000-0000-4000-a000-000000000000',
                'expectedType' => UuidV4::class,
            ],
            [
                'value' => '00000000-0000-5000-a000-000000000000',
                'expectedType' => UuidV5::class,
            ],
            [
                'value' => '00000000-0000-6000-a000-000000000000',
                'expectedType' => UuidV6::class,
            ],
            [
                'value' => '00000000-0000-7000-a000-000000000000',
                'expectedType' => UuidV7::class,
            ],
            [
                'value' => '00000000-0000-8000-a000-000000000000',
                'expectedType' => UuidV8::class,
            ],
            [
                'value' => '00000000-0000-0000-0000-000000000000',
                'expectedType' => NilUuid::class,
            ],
            [
                'value' => 'ffffffff-ffff-ffff-ffff-ffffffffffff',
                'expectedType' => MaxUuid::class,
            ],
            [
                'value' => '00000000-0000-0000-c000-000000000000',
                'expectedType' => NonstandardUuid::class,
            ],
        ];
    }

    /**
     * @dataProvider fromStringInvalidInputProvider
     */
    public function testFromStringThrowsExceptionForInvalidInput(string $input): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Identifier must be a UUID in string standard representation');

        Uuid::fromString($input);
    }

    /**
     * @return array<array{input: string}>
     */
    public function fromStringInvalidInputProvider(): array
    {
        return [
            ['input' => "\0\0\0\0\0\0\x10\0\xa0\0\0\0\0\0\0\0"],
            ['input' => '0000000000001000a000000000000000'],
            ['input' => '123.456'],
            ['input' => '340282366920937405648670758612812955647'],
        ];
    }

    public function testMax(): void
    {
        $this->assertInstanceOf(MaxUuid::class, Uuid::max());
    }

    public function testNil(): void
    {
        $this->assertInstanceOf(NilUuid::class, Uuid::nil());
    }

    public function testUuid1(): void
    {
        $this->assertInstanceOf(UuidV1::class, Uuid::uuid1());
    }

    public function testUuid1WithParams(): void
    {
        $uuid = Uuid::uuid1('0', 0, new DateTimeImmutable('1582-10-15 00:00:00'));

        $this->assertInstanceOf(UuidV1::class, $uuid);
        $this->assertSame('00000000-0000-1000-8000-010000000000', $uuid->toString());
    }

    public function testUuid2(): void
    {
        $this->assertInstanceOf(UuidV2::class, Uuid::uuid2());
    }

    public function testUuid2WithParams(): void
    {
        $uuid = Uuid::uuid2(Domain::Org, 54321, '0', 0, new DateTimeImmutable('1582-10-15 00:00:00'));

        $this->assertInstanceOf(UuidV2::class, $uuid);
        $this->assertSame('0000d431-0000-2000-8002-010000000000', $uuid->toString());
    }

    public function testUuid3(): void
    {
        $name = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.';

        $u1 = Uuid::uuid3('0000000000001000a000000000000000', $name);
        $u2 = Uuid::uuid3('00000000-0000-1000-a000-000000000000', $name);
        $u3 = Uuid::uuid3("\x00\x00\x00\x00\x00\x00\x10\x00\xa0\x00\x00\x00\x00\x00\x00\x00", $name);
        $u4 = Uuid::uuid3(Uuid::fromString('00000000-0000-1000-a000-000000000000'), $name);

        $this->assertInstanceOf(UuidV3::class, $u1);
        $this->assertInstanceOf(UuidV3::class, $u2);
        $this->assertInstanceOf(UuidV3::class, $u3);
        $this->assertInstanceOf(UuidV3::class, $u4);
        $this->assertSame('f541b9be-817b-3b15-9fb1-4647a6569948', $u1->toString());
        $this->assertTrue($u1->equals($u2));
        $this->assertTrue($u2->equals($u3));
        $this->assertTrue($u3->equals($u4));
        $this->assertTrue($u4->equals($u1));
    }

    public function testUuid3ThrowsExceptionForInvalidNamespace(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid UUID namespace: "foobar"');

        Uuid::uuid3('foobar', '');
    }

    public function testUuid4(): void
    {
        $this->assertInstanceOf(UuidV4::class, Uuid::uuid4());
    }

    public function testUuid5(): void
    {
        $name = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.';

        $u1 = Uuid::uuid5('0000000000001000a000000000000000', $name);
        $u2 = Uuid::uuid5('00000000-0000-1000-a000-000000000000', $name);
        $u3 = Uuid::uuid5("\x00\x00\x00\x00\x00\x00\x10\x00\xa0\x00\x00\x00\x00\x00\x00\x00", $name);
        $u4 = Uuid::uuid5(Uuid::fromString('00000000-0000-1000-a000-000000000000'), $name);

        $this->assertInstanceOf(UuidV5::class, $u1);
        $this->assertInstanceOf(UuidV5::class, $u2);
        $this->assertInstanceOf(UuidV5::class, $u3);
        $this->assertInstanceOf(UuidV5::class, $u4);
        $this->assertSame('ed97768f-7db1-56b5-88d3-6ad216860509', $u1->toString());
        $this->assertTrue($u1->equals($u2));
        $this->assertTrue($u2->equals($u3));
        $this->assertTrue($u3->equals($u4));
        $this->assertTrue($u4->equals($u1));
    }

    public function testUuid5ThrowsExceptionForInvalidNamespace(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid UUID namespace: "foobar"');

        Uuid::uuid5('foobar', '');
    }

    public function testUuid6(): void
    {
        $this->assertInstanceOf(UuidV6::class, Uuid::uuid6());
    }

    public function testUuid6WithParams(): void
    {
        $uuid = Uuid::uuid6('0', 0, new DateTimeImmutable('1582-10-15 00:00:00'));

        $this->assertInstanceOf(UuidV6::class, $uuid);
        $this->assertSame('00000000-0000-6000-8000-010000000000', $uuid->toString());
    }

    public function testUuid7(): void
    {
        $this->assertInstanceOf(UuidV7::class, Uuid::uuid7());
    }

    public function testUuid7WithParams(): void
    {
        $uuid = Uuid::uuid7(new DateTimeImmutable('1970-01-01 00:00:00'));

        $this->assertInstanceOf(UuidV7::class, $uuid);
        $this->assertSame('00000000-0000-7', substr($uuid->toString(), 0, 15));
    }

    public function testUuid8(): void
    {
        $uuid = Uuid::uuid8('0', '0', '0');

        $this->assertInstanceOf(UuidV8::class, $uuid);
        $this->assertSame('00000000-0000-8000-8000-000000000000', $uuid->toString());
    }
}
