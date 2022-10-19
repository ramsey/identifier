<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Uuid;

use DateTimeImmutable;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Service\Clock\FrozenClock;
use Ramsey\Identifier\Service\Counter\FrozenCounter;
use Ramsey\Identifier\Service\Dce\StaticDce;
use Ramsey\Identifier\Service\Nic\StaticNic;
use Ramsey\Identifier\Service\RandomGenerator\FrozenRandomGenerator;
use Ramsey\Identifier\Uuid\DceDomain;
use Ramsey\Identifier\Uuid\DefaultUuidFactory;
use Ramsey\Identifier\Uuid\MaxUuid;
use Ramsey\Identifier\Uuid\NilUuid;
use Ramsey\Identifier\Uuid\NonstandardUuid;
use Ramsey\Identifier\Uuid\UntypedUuid;
use Ramsey\Identifier\Uuid\UuidV1;
use Ramsey\Identifier\Uuid\UuidV2;
use Ramsey\Identifier\Uuid\UuidV3;
use Ramsey\Identifier\Uuid\UuidV4;
use Ramsey\Identifier\Uuid\UuidV5;
use Ramsey\Identifier\Uuid\UuidV6;
use Ramsey\Identifier\Uuid\UuidV7;
use Ramsey\Identifier\Uuid\UuidV8;
use Ramsey\Identifier\UuidIdentifier;
use Ramsey\Test\Identifier\TestCase;

use function sprintf;
use function substr;

use const PHP_INT_MAX;

class DefaultUuidFactoryTest extends TestCase
{
    private DefaultUuidFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new DefaultUuidFactory();
    }

    public function testCreate(): void
    {
        $this->assertInstanceOf(UuidV4::class, $this->factory->create());
    }

    /**
     * @param class-string<UuidIdentifier> $expectedType
     *
     * @dataProvider createFromBytesProvider
     */
    public function testCreateFromBytes(string $bytes, string $expectedType): void
    {
        $uuid = $this->factory->createFromBytes($bytes);

        $this->assertInstanceOf(UntypedUuid::class, $uuid);
        $this->assertInstanceOf($expectedType, $uuid->toTypedUuid());
    }

    /**
     * @return array<array{bytes: non-empty-string, expectedType: class-string<UuidIdentifier>}>
     */
    public function createFromBytesProvider(): array
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
     * @dataProvider createFromBytesInvalidInputProvider
     */
    public function testCreateFromBytesThrowsExceptionForInvalidInput(string $input): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Identifier must be a 16-byte string');

        $this->factory->createFromBytes($input);
    }

    /**
     * @return array<array{input: string}>
     */
    public function createFromBytesInvalidInputProvider(): array
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
     * @param class-string<UuidIdentifier> $expectedType
     *
     * @dataProvider createFromHexadecimalProvider
     */
    public function testCreateFromHexadecimal(string $hexadecimal, string $expectedType): void
    {
        $uuid = $this->factory->createFromHexadecimal($hexadecimal);

        $this->assertInstanceOf(UntypedUuid::class, $uuid);
        $this->assertInstanceOf($expectedType, $uuid->toTypedUuid());
    }

    /**
     * @return array<array{hexadecimal: non-empty-string, expectedType: class-string<UuidIdentifier>}>
     */
    public function createFromHexadecimalProvider(): array
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
     * @dataProvider createFromHexadecimalInvalidInputProvider
     */
    public function testCreateFromHexadecimalThrowsExceptionForInvalidInput(string $input): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Identifier must be a 32-character hexadecimal string');

        $this->factory->createFromHexadecimal($input);
    }

    /**
     * @return array<array{input: string}>
     */
    public function createFromHexadecimalInvalidInputProvider(): array
    {
        return [
            ['input' => "\0\0\0\0\0\0\x10\0\xa0\0\0\0\0\0\0\0"],
            ['input' => '00000000-0000-1000-a000-000000000000'],
            ['input' => '123.456'],
            ['input' => '340282366920937405648670758612812955647'],
            ['input' => 'fffffffffffffffffffffffffffffffff'],
        ];
    }

    public function testCreateFromIntegerThrowsExceptionForNegativeNativeInteger(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Unable to create a UUID from a negative integer');

        $this->factory->createFromInteger(-1);
    }

    public function testCreateFromIntegerThrowsExceptionForNegativeStringInteger(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Unable to create a UUID from a negative integer');

        $this->factory->createFromInteger('-9223372036854775809');
    }

    /**
     * @dataProvider createFromIntegerInvalidIntegerProvider
     */
    public function testCreateFromIntegerThrowsExceptionForInvalidInteger(int | string $input): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage(sprintf('Invalid integer: "%s"', $input));

        /** @phpstan-ignore-next-line */
        $this->factory->createFromInteger($input);
    }

    /**
     * @return array<array{input: int | string}>
     */
    public function createFromIntegerInvalidIntegerProvider(): array
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
     * @param class-string<UuidIdentifier> $expectedType
     *
     * @dataProvider createFromIntegerProvider
     */
    public function testCreateFromInteger(int | string $value, string $expectedType): void
    {
        $uuid = $this->factory->createFromInteger($value);

        $this->assertInstanceOf(UntypedUuid::class, $uuid);
        $this->assertInstanceOf($expectedType, $uuid->toTypedUuid());
    }

    /**
     * @return array<array{value: int | numeric-string, expectedType: class-string<UuidIdentifier>}>
     */
    public function createFromIntegerProvider(): array
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
     * @param class-string<UuidIdentifier> $expectedType
     *
     * @dataProvider createFromStringProvider
     */
    public function testCreateFromString(string $value, string $expectedType): void
    {
        $uuid = $this->factory->createFromString($value);

        $this->assertInstanceOf(UntypedUuid::class, $uuid);
        $this->assertInstanceOf($expectedType, $uuid->toTypedUuid());
    }

    /**
     * @return array<array{value: non-empty-string, expectedType: class-string<UuidIdentifier>}>
     */
    public function createFromStringProvider(): array
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
     * @dataProvider createFromStringInvalidInputProvider
     */
    public function testCreateFromStringThrowsExceptionForInvalidInput(string $input): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Identifier must be a UUID in string standard representation');

        $this->factory->createFromString($input);
    }

    /**
     * @return array<array{input: string}>
     */
    public function createFromStringInvalidInputProvider(): array
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
        $this->assertInstanceOf(MaxUuid::class, $this->factory->max());
    }

    public function testNil(): void
    {
        $this->assertInstanceOf(NilUuid::class, $this->factory->nil());
    }

    public function testUuid1(): void
    {
        $this->assertInstanceOf(UuidV1::class, $this->factory->v1());
    }

    public function testUuid1WithParams(): void
    {
        $uuid = $this->factory->v1('0', 0, new DateTimeImmutable('1582-10-15 00:00:00'));

        $this->assertInstanceOf(UuidV1::class, $uuid);
        $this->assertSame('00000000-0000-1000-8000-010000000000', $uuid->toString());
    }

    public function testUuid2(): void
    {
        $this->assertInstanceOf(UuidV2::class, $this->factory->v2());
    }

    public function testUuid2WithParams(): void
    {
        $uuid = $this->factory->v2(DceDomain::Org, 54321, '0', 0, new DateTimeImmutable('1582-10-15 00:00:00'));

        $this->assertInstanceOf(UuidV2::class, $uuid);
        $this->assertSame('0000d431-0000-2000-8002-010000000000', $uuid->toString());
    }

    public function testUuid3(): void
    {
        $name = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.';

        $u1 = $this->factory->v3('0000000000001000a000000000000000', $name);
        $u2 = $this->factory->v3('00000000-0000-1000-a000-000000000000', $name);
        $u3 = $this->factory->v3("\x00\x00\x00\x00\x00\x00\x10\x00\xa0\x00\x00\x00\x00\x00\x00\x00", $name);
        $u4 = $this->factory->v3($this->factory->createFromString('00000000-0000-1000-a000-000000000000'), $name);

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
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Invalid UUID namespace: "foobar"');

        $this->factory->v3('foobar', '');
    }

    public function testUuid4(): void
    {
        $this->assertInstanceOf(UuidV4::class, $this->factory->v4());
    }

    public function testUuid5(): void
    {
        $name = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.';

        $u1 = $this->factory->v5('0000000000001000a000000000000000', $name);
        $u2 = $this->factory->v5('00000000-0000-1000-a000-000000000000', $name);
        $u3 = $this->factory->v5("\x00\x00\x00\x00\x00\x00\x10\x00\xa0\x00\x00\x00\x00\x00\x00\x00", $name);
        $u4 = $this->factory->v5($this->factory->createFromString('00000000-0000-1000-a000-000000000000'), $name);

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
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Invalid UUID namespace: "foobar"');

        $this->factory->v5('foobar', '');
    }

    public function testUuid6(): void
    {
        $this->assertInstanceOf(UuidV6::class, $this->factory->v6());
    }

    public function testUuid6WithParams(): void
    {
        $uuid = $this->factory->v6('0', 0, new DateTimeImmutable('1582-10-15 00:00:00'));

        $this->assertInstanceOf(UuidV6::class, $uuid);
        $this->assertSame('00000000-0000-6000-8000-010000000000', $uuid->toString());
    }

    public function testUuid7(): void
    {
        $this->assertInstanceOf(UuidV7::class, $this->factory->v7());
    }

    public function testUuid7WithParams(): void
    {
        $uuid = $this->factory->v7(new DateTimeImmutable('1970-01-01 00:00:00'));

        $this->assertInstanceOf(UuidV7::class, $uuid);
        $this->assertSame('00000000-0000-7', substr($uuid->toString(), 0, 15));
    }

    public function testUuid8(): void
    {
        $uuid = $this->factory->v8('0', '0', '0');

        $this->assertInstanceOf(UuidV8::class, $uuid);
        $this->assertSame('00000000-0000-8000-8000-000000000000', $uuid->toString());
    }

    public function testFactoryWithDeterministicServices(): void
    {
        $counter = new FrozenCounter(1);
        $dce = new StaticDce(2, 3, 4);
        $nic = new StaticNic(5);
        $randomGenerator = new FrozenRandomGenerator(
            "\x00\x11\x22\x33\x44\x55\x66\x77\x88\x99\xaa\xbb\xcc\xdd\xee\xff",
        );
        $clock = new FrozenClock(new DateTimeImmutable('2022-09-30 00:59:09.654321'));

        $factory = new DefaultUuidFactory($clock, $counter, $dce, $nic, $randomGenerator);

        $this->assertSame('175d43ea-405b-11ed-8001-010000000005', $factory->v1()->toString());
        $this->assertSame('00000002-405b-21ed-8100-010000000005', $factory->v2()->toString());
        $this->assertSame('00000003-405b-21ed-8101-010000000005', $factory->v2(DceDomain::Group)->toString());
        $this->assertSame('00000004-405b-21ed-8102-010000000005', $factory->v2(DceDomain::Org)->toString());
        $this->assertSame('00112233-4455-4677-8899-aabbccddeeff', $factory->v4()->toString());
        $this->assertSame('1ed405b1-75d4-63ea-8001-010000000005', $factory->v6()->toString());
        $this->assertSame('01838be7-85d6-7011-a233-445566778899', $factory->v7()->toString());
    }
}
