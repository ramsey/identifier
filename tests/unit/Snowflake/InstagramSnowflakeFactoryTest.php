<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Snowflake;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Service\Clock\FrozenClock;
use Ramsey\Identifier\Service\Clock\FrozenClockSequence;
use Ramsey\Identifier\Snowflake\InstagramSnowflakeFactory;
use Ramsey\Test\Identifier\TestCase;

use function sprintf;

class InstagramSnowflakeFactoryTest extends TestCase
{
    private InstagramSnowflakeFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new InstagramSnowflakeFactory(15);
    }

    public function testCreateWithFactoryDeterministicValues(): void
    {
        $factory = new InstagramSnowflakeFactory(
            0,
            new FrozenClock(new DateTimeImmutable('2011-08-24 21:07:01.721')),
            new FrozenClockSequence(0),
        );

        $snowflake = $factory->create();

        $this->assertSame('0000000000000000', $snowflake->toHexadecimal());
        $this->assertSame('0', $snowflake->toString());
        $this->assertSame("\x00\x00\x00\x00\x00\x00\x00\x00", $snowflake->toBytes());
        $this->assertSame(0, $snowflake->toInteger());
    }

    public function testCreateFromDateTime(): void
    {
        $dateTime = new DateTimeImmutable('2022-09-25 17:32:12.345678');
        $factory = new InstagramSnowflakeFactory(1, sequence: new FrozenClockSequence(1));
        $snowflake = $factory->createFromDateTime($dateTime);

        $this->assertNotSame($dateTime, $snowflake->getDateTime());
        $this->assertSame('2022-09-25 17:32:12.345000', $snowflake->getDateTime()->format('Y-m-d H:i:s.u'));
        $this->assertSame('28bc0b6570000401', $snowflake->toHexadecimal());
    }

    public function testCreateFromBytes(): void
    {
        $snowflake = $this->factory->createFromBytes("\x01\x83\x95\xab\x83\x9a\xdf\x27");

        $this->assertSame('109095379866935079', $snowflake->toString());
    }

    public function testCreateFromBytesWithMaxValue(): void
    {
        $snowflake = $this->factory->createFromBytes("\xff\xff\xff\xff\xff\xff\xff\xff");

        $this->assertSame('18446744073709551615', $snowflake->toString());
    }

    public function testCreateFromBytesWithPhpIntMaxValue(): void
    {
        $snowflake = $this->factory->createFromBytes("\x7f\xff\xff\xff\xff\xff\xff\xff");

        $this->assertSame('9223372036854775807', $snowflake->toString());
    }

    public function testCreateFromBytesWithMinValue(): void
    {
        $snowflake = $this->factory->createFromBytes("\x00\x00\x00\x00\x00\x00\x00\x00");

        $this->assertSame('0', $snowflake->toString());
    }

    public function testCreateFromBytesThrowsException(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('The identifier must be an 8-byte octet string');

        $this->factory->createFromBytes("\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff");
    }

    public function testCreateFromHexadecimal(): void
    {
        $snowflake = $this->factory->createFromHexadecimal('018395ab839adf27');

        $this->assertSame('109095379866935079', $snowflake->toString());
    }

    public function testCreateFromHexadecimalWithMaxValue(): void
    {
        $snowflake = $this->factory->createFromHexadecimal('ffffffffffffffff');

        $this->assertSame('18446744073709551615', $snowflake->toString());
    }

    public function testCreateFromHexadecimalWithPhpIntMaxValue(): void
    {
        $snowflake = $this->factory->createFromHexadecimal('7fffffffffffffff');

        $this->assertSame('9223372036854775807', $snowflake->toString());
    }

    public function testCreateFromHexadecimalWithMinValue(): void
    {
        $snowflake = $this->factory->createFromHexadecimal('0000000000000000');

        $this->assertSame('0', $snowflake->toString());
    }

    public function testCreateFromHexadecimalThrowsExceptionForWrongLength(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('The identifier must be a 16-character hexadecimal string');

        $this->factory->createFromHexadecimal('fffffffffffffffffffffffffffffffff');
    }

    public function testCreateFromHexadecimalThrowsExceptionForNonHexadecimal(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('The identifier must be a 16-character hexadecimal string');

        $this->factory->createFromHexadecimal('fffffffffffffffg');
    }

    #[DataProvider('createFromIntegerInvalidIntegerProvider')]
    public function testCreateFromIntegerThrowsExceptionForInvalidInteger(int | string $input): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage(sprintf('Invalid Snowflake: "%s"', $input));

        /** @phpstan-ignore-next-line */
        $this->factory->createFromInteger($input);
    }

    /**
     * @return list<array{input: int | string}>
     */
    public static function createFromIntegerInvalidIntegerProvider(): array
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
     * @param int<0, max> | numeric-string $value
     */
    #[DataProvider('createFromIntegerProvider')]
    public function testCreateFromInteger(int | string $value, int | string $expected): void
    {
        $snowflake = $this->factory->createFromInteger($value);

        $this->assertSame($expected, $snowflake->toInteger());
    }

    /**
     * @return list<array{value: int | numeric-string, expected: int | numeric-string}>
     */
    public static function createFromIntegerProvider(): array
    {
        return [
            ['value' => '18446744073709551615', 'expected' => '18446744073709551615'],
            ['value' => '0', 'expected' => 0],
            ['value' => 0, 'expected' => 0],
            ['value' => 9223372036854775807, 'expected' => 9223372036854775807],
            ['value' => '9223372036854775807', 'expected' => 9223372036854775807],
        ];
    }

    public function testCreateFromString(): void
    {
        $snowflake = $this->factory->createFromString('2147483647');

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

        // This is 1023 * 2 + 1, so that we loop through the clock sequence twice.
        for ($i = 0; $i < 2047; $i++) {
            $snowflake = $this->factory->create();
            $this->assertTrue(
                $snowflake->compareTo($previous) > 0,
                sprintf(
                    'Expected %s to be greater than %s at iteration %d',
                    $snowflake->toInteger(),
                    $previous->toInteger(),
                    $i,
                ),
            );
            $previous = $snowflake;
        }
    }

    public function testCreateEachSnowflakeFromSameDateTimeIsMonotonicallyIncreasing(): void
    {
        $dateTime = new DateTimeImmutable();

        $previous = $this->factory->createFromDateTime($dateTime);

        // This is 1023 * 2 + 1, so that we loop through the clock sequence twice.
        for ($i = 0; $i < 2047; $i++) {
            $snowflake = $this->factory->createFromDateTime($dateTime);
            $this->assertTrue(
                $snowflake->compareTo($previous) > 0,
                sprintf(
                    'Expected %s to be greater than %s at iteration %d',
                    $snowflake->toInteger(),
                    $previous->toInteger(),
                    $i,
                ),
            );
            $this->assertSame($dateTime->format('Y-m-d H:i:s'), $snowflake->getDateTime()->format('Y-m-d H:i:s'));
            $previous = $snowflake;
        }
    }

    public function testCreateFromDateThrowsExceptionForTooEarlyTimestamp(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage(
            'Timestamp may not be earlier than the Instagram epoch, 2011-08-24T21:07:01.721Z',
        );

        $this->factory->createFromDateTime(new DateTimeImmutable('2011-08-24 21:07:01.720'));
    }

    public function testCreateFromDateTimeForOutOfBoundsDateTime(): void
    {
        $factory = new InstagramSnowflakeFactory(0x1fff, sequence: new FrozenClockSequence(0x3ff));

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage(
            'Instagram Snowflakes cannot have a date-time greater than 2081-04-30T12:54:37.272Z',
        );

        $factory->createFromDateTime(new DateTimeImmutable('2081-04-30 12:54:37.273'));
    }

    public function testCreateFromDateTimeWithMaxValuesReturnsMaxIdentifier(): void
    {
        $factory = new InstagramSnowflakeFactory(0x1fff, sequence: new FrozenClockSequence(0x3ff));
        $snowflake = $factory->createFromDateTime(new DateTimeImmutable('2081-04-30 12:54:37.272'));

        $this->assertSame('18446744073709551615', $snowflake->toInteger());
    }
}
