<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Service\Clock;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Service\Cache\InMemoryCache;
use Ramsey\Identifier\Service\Clock\FrozenClock;
use Ramsey\Identifier\Service\Clock\GeneratorState;
use Ramsey\Identifier\Service\Clock\InvalidGeneratorState;
use Ramsey\Identifier\Service\Clock\Rfc4122ClockSequence;
use Ramsey\Identifier\Service\Nic\StaticNic;

use const PHP_INT_MAX;

class Rfc4122ClockSequenceTest extends TestCase
{
    public function testSequenceRemainsConstant(): void
    {
        $sequence = new Rfc4122ClockSequence(initialValue: 0);

        $previous = $sequence->current();
        $this->assertSame(0, $previous);

        for ($i = 0; $i < 1000; $i++) {
            $next = $sequence->next();
            $this->assertSame($next, $sequence->current());
            $this->assertSame($previous, $next);
            $previous = $next;
        }
    }

    public function testSequenceAdvancesIfClockIsSetBackwards(): void
    {
        $sequence = new Rfc4122ClockSequence(
            initialValue: 0,
            clock: new FrozenClock(new DateTimeImmutable()),
        );

        // This initializes the local cache with a value and sets the timestamp
        // two days in the future.
        $this->assertSame(0, $sequence->next(dateTime: new DateTimeImmutable('+2 days')));

        // Now, when called and getting the system time, the time is earlier
        // than the timestamp we just gave it, so we assume the clock has been
        // set backwards, and the value should increment.
        $this->assertSame(1, $sequence->next());

        // But the next time we call, it should still be 1.
        $this->assertSame(1, $sequence->next());
    }

    #[TestWith(['1970-01-01 00:00:01.200000', '1970-01-01 00:00:01.800000'])]
    #[TestWith(['1969-12-31 23:59:59.200000', '1969-12-31 23:59:59.800000'])]
    #[TestWith(['1969-12-31 23:59:58.900000', '1969-12-31 23:59:59.100000'])]
    #[TestWith(['1969-12-31 23:59:59.999999', '1970-01-01 00:00:00.000000'])]
    #[TestWith(['1960-06-01 12:00:00.250000', '1960-06-01 12:00:00.250001'])]
    public function testSequenceRemainsConstantWhenDateTimeMovesForward(string $earlier, string $later): void
    {
        $sequence = new Rfc4122ClockSequence(initialValue: 0);

        $this->assertSame(0, $sequence->next('node', new DateTimeImmutable($earlier)));
        $this->assertSame(0, $sequence->next('node', new DateTimeImmutable($later)));
        $this->assertSame(0, $sequence->next('node', new DateTimeImmutable($later)));
    }

    #[TestWith(['1970-01-01 00:00:01.200000', '1970-01-01 00:00:01.800000'])]
    #[TestWith(['1969-12-31 23:59:59.200000', '1969-12-31 23:59:59.800000'])]
    #[TestWith(['1969-12-31 23:59:58.900000', '1969-12-31 23:59:59.100000'])]
    #[TestWith(['1969-12-31 23:59:59.999999', '1970-01-01 00:00:00.000000'])]
    #[TestWith(['1960-06-01 12:00:00.250000', '1960-06-01 12:00:00.250001'])]
    public function testSequenceAdvancesWhenDateTimeMovesBackward(string $earlier, string $later): void
    {
        $sequence = new Rfc4122ClockSequence(initialValue: 0);

        $this->assertSame(0, $sequence->next('node', new DateTimeImmutable($later)));
        $this->assertSame(1, $sequence->next('node', new DateTimeImmutable($earlier)));
        $this->assertSame(1, $sequence->next('node', new DateTimeImmutable($later)));
    }

    #[TestWith(['@9223372036855', '@9223372036856'])]
    #[TestWith(['@-9223372036856', '@-9223372036855'])]
    public function testSequenceAcceptsDateTimesFarFromTheUnixEpoch(string $earlier, string $later): void
    {
        $sequence = new Rfc4122ClockSequence(initialValue: 0);

        $this->assertSame(0, $sequence->next('node', new DateTimeImmutable($earlier)));
        $this->assertSame(0, $sequence->next('node', new DateTimeImmutable($later)));
    }

    public function testSequenceRollsOverWhenMaxValueIsReached(): void
    {
        $sequence = new Rfc4122ClockSequence(
            initialValue: PHP_INT_MAX,
            clock: new FrozenClock(new DateTimeImmutable()),
        );

        // This initializes the local cache with a value and sets the timestamp
        // two days in the future.
        $this->assertSame(PHP_INT_MAX, $sequence->next(dateTime: new DateTimeImmutable('+2 days')));

        // Now, when called and getting the system time, the time is earlier
        // than the timestamp we just gave it, so we assume the clock has been
        // set backwards, and the value should roll over to zero.
        $this->assertSame(0, $sequence->next());

        // And the next time we call, it should still be zero.
        $this->assertSame(0, $sequence->next());
    }

    public function testGeneratorStateInCacheIsCorrupted(): void
    {
        $nic = new StaticNic('1234567890ab');
        $cache = new InMemoryCache();

        $cacheKey = '__ramsey_id_122f13ab|' . $nic->address();
        $cache->set($cacheKey, 'not a GeneratorState instance');

        $sequence = new Rfc4122ClockSequence(nic: $nic, cache: $cache);

        $this->expectException(InvalidGeneratorState::class);
        $this->expectExceptionMessage('The generator state must be an instance of ' . GeneratorState::class);

        $sequence->next();
    }

    public function testGenerateNewValueWhenNodeChanges(): void
    {
        $nic = new StaticNic('1234567890ab');
        $cache = new InMemoryCache();

        $cacheKey = '__ramsey_id_122f13ab|' . $nic->address();
        $cache->set($cacheKey, new GeneratorState(
            node: $nic->address(),
            sequence: 1000,
            timestamp: (int) (new DateTimeImmutable())->format('Uu'),
        ));

        $sequence = new Rfc4122ClockSequence(nic: $nic, cache: $cache);

        $this->assertSame(1000, $sequence->next());
        $this->assertSame(1000, $sequence->next());
        $this->assertNotSame(1000, $sequence->next(state: 'ba0987654321'));
    }

    public function testSequenceInitialValueIsNegativeInteger(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('The clock sequence initial value must be a positive integer or null');

        /** @phpstan-ignore argument.type */
        new Rfc4122ClockSequence(initialValue: -1);
    }

    public function testCurrentWhenStateIsEmptyString(): void
    {
        $sequence = new Rfc4122ClockSequence();

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage(
            'When getting the current or next clock sequence value, the state must be a non-empty string or null',
        );

        /** @phpstan-ignore argument.type */
        $sequence->current(state: '');
    }

    public function testNextWhenStateIsEmptyString(): void
    {
        $sequence = new Rfc4122ClockSequence();

        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage(
            'When getting the current or next clock sequence value, the state must be a non-empty string or null',
        );

        /** @phpstan-ignore argument.type */
        $sequence->next(state: '');
    }
}
