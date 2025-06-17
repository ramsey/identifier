<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Snowflake;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Service\BytesGenerator\FixedBytesGenerator;
use Ramsey\Identifier\Service\Clock\FrozenClock;
use Ramsey\Identifier\Service\Hash\FixedHash;
use Ramsey\Identifier\Service\Sequence\FrozenSequence;
use Ramsey\Identifier\Snowflake\MastodonSnowflakeFactory;
use Ramsey\Test\Identifier\TestCase;

use function sprintf;
use function usleep;

class MastodonSnowflakeFactoryTest extends TestCase
{
    private MastodonSnowflakeFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new MastodonSnowflakeFactory('a_database_table');
    }

    public function testCreateWithFactoryDeterministicValues(): void
    {
        $factory = new MastodonSnowflakeFactory(
            clock: new FrozenClock(new DateTimeImmutable('1970-01-01T00:00:00.000Z')),
            sequence: new FrozenSequence(0),
            bytesGenerator: new FixedBytesGenerator("\x00"),
        );

        $snowflake = $factory->create();

        // The "de" (i.e., 222) value is a result of the hashing algorithm used. This is what is evaluated:
        //
        //     unpack('n', substr(md5(null . '00000000000000000000000000000000' . 0, true), 0, 2))[1]
        //
        // This evaluates to 222, or "de" (hexadecimal).
        $this->assertSame('00000000000000de', $snowflake->toHexadecimal());
        $this->assertSame('222', $snowflake->toString());
        $this->assertSame("\x00\x00\x00\x00\x00\x00\x00\xde", $snowflake->toBytes());
        $this->assertSame(222, $snowflake->toInteger());
    }

    public function testCreateFromDateTime(): void
    {
        $dateTime = new DateTimeImmutable('2022-09-25 17:32:12.345678');
        $factory = new MastodonSnowflakeFactory(
            sequence: new FrozenSequence(1),
            bytesGenerator: new FixedBytesGenerator("\x00"),
        );
        $snowflake = $factory->createFromDateTime($dateTime);

        $this->assertNotSame($dateTime, $snowflake->getDateTime());
        $this->assertSame('2022-09-25 17:32:12.345000', $snowflake->getDateTime()->format('Y-m-d H:i:s.u'));
        $this->assertSame('018375b4e2b99a3b', $snowflake->toHexadecimal());
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

    public function testSnowflakesCreatedInDifferentMillisecondsAreMonotonicallyIncreasing(): void
    {
        $previous = $this->factory->create();

        for ($i = 0; $i < 500; $i++) {
            // Sleep for 1 millisecond to advance the clock, since Mastodon Snowflakes are not guaranteed to be
            // monotonically increasing when generated within the same millisecond.
            usleep(1000);
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

    public function testCreateFromDateThrowsExceptionForTooEarlyTimestamp(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage(
            'Timestamp may not be earlier than the Mastodon epoch, 1970-01-01T00:00:00.000Z',
        );

        $this->factory->createFromDateTime(new DateTimeImmutable('1969-12-31 23:59:59.999'));
    }

    public function testCreateFromDateTimeForOutOfBoundsDateTime(): void
    {
        $factory = new MastodonSnowflakeFactory();

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage(
            'Mastodon Snowflakes cannot have a date-time greater than 10889-08-02T05:31:50.655Z',
        );

        $factory->createFromDateTime(new DateTimeImmutable('@281474976710.656'));
    }

    public function testCreateFromDateTimeWithMaxValuesReturnsMaxIdentifier(): void
    {
        $factory = new MastodonSnowflakeFactory(
            sequence: new FrozenSequence(0x10000),
            hash: new FixedHash('ffffffffffffffffffffffffffffffff'),
        );

        $snowflake = $factory->createFromDateTime(new DateTimeImmutable('@281474976710.655'));

        $this->assertSame('18446744073709551615', $snowflake->toInteger());
    }
}
