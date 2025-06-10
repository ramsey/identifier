<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Service\Clock;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Ramsey\Identifier\Service\Cache\InMemoryCache;
use Ramsey\Identifier\Service\Clock\FrozenClock;
use Ramsey\Identifier\Service\Clock\GeneratorState;
use Ramsey\Identifier\Service\Clock\InvalidGeneratorState;
use Ramsey\Identifier\Service\Clock\MonotonicClockSequence;
use Ramsey\Identifier\Service\Nic\StaticNic;

use function substr;

use const PHP_INT_MAX;

class MonotonicClockSequenceTest extends TestCase
{
    public function testSequenceIsMonotonic(): void
    {
        $sequence = new MonotonicClockSequence(
            initialValue: 0,
            clock: new FrozenClock(new DateTimeImmutable()),
        );

        $previous = $sequence->current();
        $this->assertSame(0, $previous);

        for ($i = 0; $i < 1000; $i++) {
            $next = $sequence->next();
            $this->assertSame($next, $sequence->current());
            $this->assertGreaterThan($previous, $next);
            $previous = $next;
        }
    }

    public function testSequenceRollsOverWhenMaxIsReached(): void
    {
        $sequence = new MonotonicClockSequence(
            initialValue: PHP_INT_MAX,
            clock: new FrozenClock(new DateTimeImmutable()),
        );

        $this->assertSame(PHP_INT_MAX, $sequence->current());
        $this->assertSame(0, $sequence->next());
    }

    public function testGeneratorStateInCacheIsCorrupted(): void
    {
        $nic = new StaticNic('1234567890ab');
        $clock = new FrozenClock(new DateTimeImmutable());
        $cache = new InMemoryCache();

        $cacheKey = '__ramsey_id_4cdb157d|' . $nic->address() . '|' . $clock->now()->format('Uv');
        $cache->set($cacheKey, 'not a GeneratorState instance');

        $sequence = new MonotonicClockSequence(nic: $nic, clock: $clock, cache: $cache);

        $this->expectException(InvalidGeneratorState::class);
        $this->expectExceptionMessage('The generator state must be an instance of ' . GeneratorState::class);

        $sequence->next();
    }

    public function testGenerateNewValueWhenTimestampChanges(): void
    {
        $nic = new StaticNic('1234567890ab');
        $cache = new InMemoryCache();
        $clock = new FrozenClock(new DateTimeImmutable());

        $cacheKey = '__ramsey_id_4cdb157d|' . $nic->address() . '|' . $clock->now()->format('Uv');
        $cache->set($cacheKey, new GeneratorState(
            node: $nic->address(),
            sequence: 1000,
            timestamp: (int) $clock->now()->format('Uu'),
        ));

        $sequence = new MonotonicClockSequence(nic: $nic, clock: $clock, cache: $cache);

        $this->assertSame(1001, $sequence->next());
        $this->assertSame(1002, $sequence->next());
        $this->assertSame(1003, $sequence->next());

        $ts = (int) $clock->now()->format('Uv');
        $ts++;
        $ts = (string) $ts;
        $date = new DateTimeImmutable('@' . substr($ts, 0, -6) . '.' . substr($ts, -6));

        $this->assertNotSame(1004, $sequence->next(dateTime: $date));
    }

    public function testGenerateNewValueWhenNodeChanges(): void
    {
        $nic = new StaticNic('1234567890ab');
        $cache = new InMemoryCache();
        $clock = new FrozenClock(new DateTimeImmutable());

        $cacheKey = '__ramsey_id_4cdb157d|' . $nic->address() . '|' . $clock->now()->format('Uv');
        $cache->set($cacheKey, new GeneratorState(
            node: $nic->address(),
            sequence: 1000,
            timestamp: (int) $clock->now()->format('Uv'),
        ));

        $sequence = new MonotonicClockSequence(nic: $nic, clock: $clock, cache: $cache);

        $this->assertSame(1001, $sequence->next());
        $this->assertSame(1002, $sequence->next());
        $this->assertSame(1003, $sequence->next());
        $this->assertNotSame(1004, $sequence->next(state: 'ba0987654321'));
    }
}
