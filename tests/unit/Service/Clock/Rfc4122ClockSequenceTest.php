<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Service\Clock;

use DateTimeImmutable;
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

        // This initializes the local cache with a value and sets the timestamp two days in the future.
        $this->assertSame(0, $sequence->next(dateTime: new DateTimeImmutable('+2 days')));

        // Now, when called and getting the system time, the time is earlier than the timestamp we just gave it, so
        // we assume the clock has been set backwards, and the value should increment.
        $this->assertSame(1, $sequence->next());

        // But the next time we call, it should still be 1.
        $this->assertSame(1, $sequence->next());
    }

    public function testSequenceRollsOverWhenMaxValueIsReached(): void
    {
        $sequence = new Rfc4122ClockSequence(
            initialValue: PHP_INT_MAX,
            clock: new FrozenClock(new DateTimeImmutable()),
        );

        // This initializes the local cache with a value and sets the timestamp two days in the future.
        $this->assertSame(PHP_INT_MAX, $sequence->next(dateTime: new DateTimeImmutable('+2 days')));

        // Now, when called and getting the system time, the time is earlier than the timestamp we just gave it, so
        // we assume the clock has been set backwards, and the value should roll over to zero.
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
