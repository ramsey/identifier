<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Snowflake;

use DateTimeImmutable;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Service\Clock\FrozenClock;
use Ramsey\Identifier\Service\Clock\FrozenSequence;
use Ramsey\Identifier\Service\Os\Os;
use Ramsey\Identifier\Snowflake\InstagramSnowflake;
use Ramsey\Identifier\Snowflake\InstagramSnowflakeFactory;
use Ramsey\Test\Identifier\TestCase;

use function gmdate;
use function sprintf;

use const PHP_INT_MAX;
use const PHP_INT_SIZE;

class InstagramSnowflakeFactoryTest extends TestCase
{
    private Os $os32;
    private InstagramSnowflakeFactory $factory;

    protected function setUp(): void
    {
        $this->os32 = $this->mockery(Os::class, ['getIntSize' => 4]);
        $this->factory = new InstagramSnowflakeFactory(15);
    }

    public function testCreate(): void
    {
        $snowflake = $this->factory->create();

        $this->assertInstanceOf(InstagramSnowflake::class, $snowflake);
    }

    public function testCreateWithFactoryDeterministicValues(): void
    {
        $factory = new InstagramSnowflakeFactory(
            0,
            new FrozenClock(new DateTimeImmutable('2011-08-24 21:07:01.721')),
            new FrozenSequence(0),
        );

        $snowflake = $factory->create();

        $this->assertInstanceOf(InstagramSnowflake::class, $snowflake);
        $this->assertSame('0000000000000000', $snowflake->toHexadecimal());
        $this->assertSame('0', $snowflake->toString());
        $this->assertSame("\x00\x00\x00\x00\x00\x00\x00\x00", $snowflake->toBytes());

        if (PHP_INT_SIZE >= 8) {
            $this->assertSame(0, $snowflake->toInteger());
        } else {
            $this->assertSame('0', $snowflake->toInteger());
        }
    }

    public function testCreateFromDateTime(): void
    {
        $dateTime = new DateTimeImmutable('2022-09-25 17:32:12.345678');
        $factory = new InstagramSnowflakeFactory(1, sequence: new FrozenSequence(1));
        $snowflake = $factory->createFromDateTime($dateTime);

        $this->assertInstanceOf(InstagramSnowflake::class, $snowflake);
        $this->assertNotSame($dateTime, $snowflake->getDateTime());
        $this->assertSame('2022-09-25 17:32:12.345000', $snowflake->getDateTime()->format('Y-m-d H:i:s.u'));
        $this->assertSame('28bc0b6570000401', $snowflake->toHexadecimal());
    }

    public function testCreateFromDateTimeOn32Bit(): void
    {
        $dateTime = new DateTimeImmutable('2022-09-25 17:32:12.345678');
        $factory = new InstagramSnowflakeFactory(1, sequence: new FrozenSequence(1), os: $this->os32);
        $snowflake = $factory->createFromDateTime($dateTime);

        $this->assertInstanceOf(InstagramSnowflake::class, $snowflake);
        $this->assertNotSame($dateTime, $snowflake->getDateTime());
        $this->assertSame('2022-09-25 17:32:12.345000', $snowflake->getDateTime()->format('Y-m-d H:i:s.u'));
        $this->assertSame('28bc0b6570000401', $snowflake->toHexadecimal());
    }

    public function testCreateFromBytes(): void
    {
        $snowflake = $this->factory->createFromBytes("\x01\x83\x95\xab\x83\x9a\xdf\x27");

        $this->assertInstanceOf(InstagramSnowflake::class, $snowflake);
        $this->assertSame('109095379866935079', $snowflake->toString());
    }

    public function testCreateFromBytesWithMaxValue(): void
    {
        $snowflake = $this->factory->createFromBytes("\x7f\xff\xff\xff\xff\xff\xff\xff");

        $this->assertInstanceOf(InstagramSnowflake::class, $snowflake);
        $this->assertSame('9223372036854775807', $snowflake->toString());
    }

    public function testCreateFromBytesWithMinValue(): void
    {
        $snowflake = $this->factory->createFromBytes("\x00\x00\x00\x00\x00\x00\x00\x00");

        $this->assertInstanceOf(InstagramSnowflake::class, $snowflake);
        $this->assertSame('0', $snowflake->toString());
    }

    public function testCreateFromBytesOn32Bit(): void
    {
        $factory = new InstagramSnowflakeFactory(1, os: $this->os32);
        $snowflake = $factory->createFromBytes("\x01\x83\x95\xab\x83\x9a\xdf\x27");

        $this->assertInstanceOf(InstagramSnowflake::class, $snowflake);
        $this->assertSame('109095379866935079', $snowflake->toString());
    }

    public function testCreateFromBytesWithMaxValueOn32Bit(): void
    {
        $factory = new InstagramSnowflakeFactory(1, os: $this->os32);
        $snowflake = $factory->createFromBytes("\x7f\xff\xff\xff\xff\xff\xff\xff");

        $this->assertInstanceOf(InstagramSnowflake::class, $snowflake);
        $this->assertSame('9223372036854775807', $snowflake->toString());
    }

    public function testCreateFromBytesWithMinValueOn32Bit(): void
    {
        $factory = new InstagramSnowflakeFactory(1, os: $this->os32);
        $snowflake = $factory->createFromBytes("\x00\x00\x00\x00\x00\x00\x00\x00");

        $this->assertInstanceOf(InstagramSnowflake::class, $snowflake);
        $this->assertSame('0', $snowflake->toString());
    }

    public function testCreateFromBytesThrowsException(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Identifier must be an 8-byte string');

        $this->factory->createFromBytes("\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff");
    }

    public function testCreateFromHexadecimal(): void
    {
        $snowflake = $this->factory->createFromHexadecimal('018395ab839adf27');

        $this->assertInstanceOf(InstagramSnowflake::class, $snowflake);
        $this->assertSame('109095379866935079', $snowflake->toString());
    }

    public function testCreateFromHexadecimalWithMaxValue(): void
    {
        $snowflake = $this->factory->createFromHexadecimal('7fffffffffffffff');

        $this->assertInstanceOf(InstagramSnowflake::class, $snowflake);
        $this->assertSame('9223372036854775807', $snowflake->toString());
    }

    public function testCreateFromHexadecimalWithMinValue(): void
    {
        $snowflake = $this->factory->createFromHexadecimal('0000000000000000');

        $this->assertInstanceOf(InstagramSnowflake::class, $snowflake);
        $this->assertSame('0', $snowflake->toString());
    }

    public function testCreateFromHexadecimalOn32Bit(): void
    {
        $factory = new InstagramSnowflakeFactory(1, os: $this->os32);
        $snowflake = $factory->createFromHexadecimal('018395ab839adf27');

        $this->assertInstanceOf(InstagramSnowflake::class, $snowflake);
        $this->assertSame('109095379866935079', $snowflake->toString());
    }

    public function testCreateFromHexadecimalWithMaxValueOn32Bit(): void
    {
        $factory = new InstagramSnowflakeFactory(1, os: $this->os32);
        $snowflake = $factory->createFromHexadecimal('7fffffffffffffff');

        $this->assertInstanceOf(InstagramSnowflake::class, $snowflake);
        $this->assertSame('9223372036854775807', $snowflake->toString());
    }

    public function testCreateFromHexadecimalWithMinValueOn32Bit(): void
    {
        $factory = new InstagramSnowflakeFactory(1, os: $this->os32);
        $snowflake = $factory->createFromHexadecimal('0000000000000000');

        $this->assertInstanceOf(InstagramSnowflake::class, $snowflake);
        $this->assertSame('0', $snowflake->toString());
    }

    public function testCreateFromHexadecimalThrowsExceptionForWrongLength(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Identifier must be a 16-character hexadecimal string');

        $this->factory->createFromHexadecimal('fffffffffffffffffffffffffffffffff');
    }

    public function testCreateFromHexadecimalThrowsExceptionForNonHexadecimal(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Identifier must be a 16-character hexadecimal string');

        $this->factory->createFromHexadecimal('fffffffffffffffg');
    }

    /**
     * @dataProvider createFromIntegerInvalidIntegerProvider
     */
    public function testCreateFromIntegerThrowsExceptionForInvalidInteger(int | string $input): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage(sprintf('Invalid Snowflake: "%s"', $input));

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
            ['input' => 'foobar'],
            ['input' => -1],
            ['input' => '-9223372036854775808'],
            ['input' => -2147483646],
        ];
    }

    /**
     * @param int | numeric-string $value
     *
     * @dataProvider createFromIntegerProvider
     */
    public function testCreateFromInteger(int | string $value): void
    {
        $snowflake = $this->factory->createFromInteger($value);

        $this->assertInstanceOf(InstagramSnowflake::class, $snowflake);
    }

    /**
     * @return array<array{value: int | numeric-string}>
     */
    public function createFromIntegerProvider(): array
    {
        return [
            ['value' => '340282366920937934553716840013076889599'],
            ['value' => '0'],
            ['value' => 0],
            ['value' => '340282366920938463463374607431768211455'],
            ['value' => PHP_INT_MAX],
            ['value' => (string) PHP_INT_MAX],
        ];
    }

    public function testCreateFromString(): void
    {
        $snowflake = $this->factory->createFromString('2147483647');

        $this->assertInstanceOf(InstagramSnowflake::class, $snowflake);
        $this->assertSame('2147483647', $snowflake->toString());
    }

    public function testCreateFromStringThrowsExceptionForInvalidString(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Invalid Snowflake: "123.456"');

        $this->factory->createFromString('123.456');
    }

    public function testCreateEachSnowflakeIsMonotonicallyIncreasing(): void
    {
        $previous = $this->factory->create();

        for ($i = 0; $i < 25; $i++) {
            $snowflake = $this->factory->create();
            $now = gmdate('Y-m-d H:i');
            $this->assertTrue($snowflake->compareTo($previous) > 0);
            $this->assertSame($now, $snowflake->getDateTime()->format('Y-m-d H:i'));
            $previous = $snowflake;
        }
    }

    public function testCreateEachSnowflakeFromSameDateTimeIsMonotonicallyIncreasing(): void
    {
        $dateTime = new DateTimeImmutable();

        $previous = $this->factory->createFromDateTime($dateTime);

        for ($i = 0; $i < 25; $i++) {
            $snowflake = $this->factory->createFromDateTime($dateTime);
            $this->assertTrue($snowflake->compareTo($previous) > 0);
            $this->assertSame($dateTime->format('Y-m-d H:i'), $snowflake->getDateTime()->format('Y-m-d H:i'));
            $previous = $snowflake;
        }
    }

    public function testCreateFromDateThrowsExceptionForTooEarlyTimestamp(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage(
            'Timestamp may not be earlier than the Instagram epoch, 2011-08-24 21:07:01.721 +00:00',
        );

        $this->factory->createFromDateTime(new DateTimeImmutable('2011-08-24 21:07:01.720'));
    }
}
