<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Service\Cache;

use DateInterval;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;
use Ramsey\Identifier\Service\Cache\InMemoryCache;
use Ramsey\Identifier\Service\Cache\InvalidCacheArgument;
use Ramsey\Identifier\Service\Clock\FrozenClock;
use stdClass;

use function array_keys;
use function array_slice;
use function count;
use function iterator_to_array;
use function usleep;

#[TestDox(InMemoryCache::class)]
class InMemoryCacheTest extends TestCase
{
    #[TestDox('get() returns the default value when the key is not found')]
    public function testGetCacheReturnsDefaultValue(): void
    {
        $inMemoryCache = new InMemoryCache();

        $this->assertNull($inMemoryCache->get('foo'));
        $this->assertSame('bar', $inMemoryCache->get('foo', 'bar'));
    }

    #[TestDox('get() returns the cache item that was set with set()')]
    public function testGetCacheReturnsCacheItem(): void
    {
        $inMemoryCache = new InMemoryCache();

        $this->assertTrue($inMemoryCache->set('foo', 1234));
        $this->assertSame(1234, $inMemoryCache->get('foo'));
    }

    #[TestDox('delete() removes the item from the cache')]
    public function testDelete(): void
    {
        $inMemoryCache = new InMemoryCache();
        $inMemoryCache->set('foo', 1234);

        $this->assertSame(1234, $inMemoryCache->get('foo'));
        $this->assertTrue($inMemoryCache->delete('foo'));
        $this->assertNull($inMemoryCache->get('foo'));
    }

    #[TestDox('clear() removes all items from the cache')]
    public function testClear(): void
    {
        $values = [
            'foo' => 1234,
            'bar' => false,
            'baz' => 'some string value',
            'qux' => [1234, true, false, null, 'some string value'],
            'quux' => new stdClass(),
            'corge' => 1234.5678,
        ];

        $inMemoryCache = new InMemoryCache();

        foreach ($values as $key => $value) {
            $inMemoryCache->set($key, $value);
        }

        foreach ($values as $key => $value) {
            $this->assertSame($value, $inMemoryCache->get($key));
        }

        $this->assertTrue($inMemoryCache->clear());

        foreach (array_keys($values) as $key) {
            $this->assertNull($inMemoryCache->get($key));
        }
    }

    #[TestDox('getMultiple() returns values for items set with setMultiple() and default for items not found')]
    public function testGetMultiple(): void
    {
        $values = [
            'foo' => 1234,
            'bar' => false,
            'baz' => 'some string value',
            'qux' => [1234, true, false, null, 'some string value'],
            'quux' => new stdClass(),
            'corge' => 1234.5678,
        ];

        $inMemoryCache = new InMemoryCache();

        $this->assertTrue($inMemoryCache->setMultiple($values));

        $returnedValues = iterator_to_array($inMemoryCache->getMultiple(
            ['grault', ...array_keys($values), 'garply'],
            'the default value',
        ));

        $this->assertSame(
            ['grault' => 'the default value', ...$values, 'garply' => 'the default value'],
            $returnedValues,
        );
    }

    #[TestDox('deleteMultiple() removes multiple items from the cache')]
    public function testDeleteMultiple(): void
    {
        $values = [
            'foo' => 1234,
            'bar' => false,
            'baz' => 'some string value',
            'qux' => [1234, true, false, null, 'some string value'],
            'quux' => new stdClass(),
            'corge' => 1234.5678,
        ];

        $inMemoryCache = new InMemoryCache();
        $inMemoryCache->setMultiple($values);

        $this->assertTrue($inMemoryCache->deleteMultiple(['foo', 'baz', 'quux']));

        $returnedValues = iterator_to_array($inMemoryCache->getMultiple(array_keys($values)));

        $this->assertSame(
            [
                'foo' => null,
                'bar' => false,
                'baz' => null,
                'qux' => [1234, true, false, null, 'some string value'],
                'quux' => null,
                'corge' => 1234.5678,
            ],
            $returnedValues,
        );
    }

    #[TestDox('has() returns true if the item exists in the cache and false otherwise')]
    public function testHas(): void
    {
        $inMemoryCache = new InMemoryCache();
        $inMemoryCache->set('foo', 1234);

        $this->assertTrue($inMemoryCache->has('foo'));
        $this->assertFalse($inMemoryCache->has('bar'));
    }

    #[TestDox('cache items expire after the specified TTL when $_dataName')]
    #[DataProvider('ttlProvider')]
    public function testTtl(DateInterval | int | null $ttl): void
    {
        // The default TTL is negative in order to test expiration of TTLs.
        $inMemoryCache = new InMemoryCache(defaultTtl: -10);

        $inMemoryCache->set('foo', 'some value', $ttl);

        $this->assertNull($inMemoryCache->get('foo'));
    }

    /**
     * @return array<string, array{ttl: DateInterval | int | null}>
     */
    public static function ttlProvider(): array
    {
        // These values are negative in order to test expiration of TTLs.
        return [
            'using the default TTL' => ['ttl' => null],
            'providing a DateInterval TTL' => [
                'ttl' => (function (): DateInterval {
                    $interval = new DateInterval('PT30M');
                    $interval->invert = 1;

                    return $interval;
                })(),
            ],
            'providing an integer TTL' => ['ttl' => -900],
        ];
    }

    #[TestDox('set() evicts the least recently used item when the clock is before the Unix Epoch')]
    public function testCacheEvictionBeforeUnixEpoch(): void
    {
        $clock = new class implements ClockInterface {
            /**
             * @var list<string>
             */
            private array $times = [
                '1969-12-31 23:59:58.900000 +00:00',
                '1969-12-31 23:59:59.000000 +00:00',
                '1969-12-31 23:59:59.400000 +00:00',
                '1969-12-31 23:59:59.500000 +00:00',
            ];

            private int $calls = 0;

            public function now(): DateTimeImmutable
            {
                // Each call returns the next time, and the last time from then
                // on.
                $time = $this->times[$this->calls] ?? $this->times[count($this->times) - 1];
                $this->calls++;

                return new DateTimeImmutable($time);
            }
        };

        $inMemoryCache = new InMemoryCache(cacheSize: 2, clock: $clock);

        // The cache reads the clock twice for each item it stores: once for
        // the expiration time and once for the time of last use. So 'foo' was
        // last used 1.0 second before the Unix Epoch and 'bar' 0.5 seconds
        // before it, and 'foo' is the least recently used item.
        $inMemoryCache->set('foo', 1234);
        $inMemoryCache->set('bar', 5678);
        $inMemoryCache->set('baz', 9012);

        $this->assertNull($inMemoryCache->get('foo'));
        $this->assertSame(5678, $inMemoryCache->get('bar'));
        $this->assertSame(9012, $inMemoryCache->get('baz'));
    }

    #[TestDox('set() evicts the least recently used item when the cache is full')]
    public function testCacheEviction(): void
    {
        $values = [
            'foo' => 1234,
            'bar' => false,
            'baz' => 'some string value',
            'qux' => [1234, true, false, null, 'some string value'],
            'quux' => new stdClass(),
            'corge' => 1234.5678,
            'grault' => 'some other string value',
        ];

        $inMemoryCache = new InMemoryCache(cacheSize: 3);
        $inMemoryCache->setMultiple(array_slice($values, 0, 3));

        // Wait a tiny bit.
        usleep(100);

        // Access 'bar' first, so it's the least most recently accessed item.
        $this->assertFalse($inMemoryCache->get('bar'));

        // Wait a tiny bit more.
        usleep(100);

        // Access 'foo' next, so it's the second least most recently accessed
        // item.
        $this->assertSame(1234, $inMemoryCache->get('foo'));

        // Wait a little bit more.
        usleep(100);

        // Access 'baz' next, so it's the most recently accessed item.
        $this->assertSame('some string value', $inMemoryCache->get('baz'));

        // Wait just a little bit more.
        usleep(100);

        // Add a new value, which should evict 'bar' from the cache.
        $inMemoryCache->set('qux', $values['qux']);

        // 'bar' should no longer exist in the cache.
        $this->assertNull($inMemoryCache->get('bar'));

        // Add another new value, which should evict 'foo' from the cache.
        $inMemoryCache->set('quux', $values['quux']);

        // 'foo' should no longer exist in the cache.
        $this->assertNull($inMemoryCache->get('foo'));

        // Add 'corge', but give it a TTL in the past, so it's considered
        // invalid and will be evicted before 'baz'.
        $inMemoryCache->set('corge', $values['corge'], -30);

        // Add a new value, which should evict 'corge' from the cache.
        $inMemoryCache->set('quux', $values['quux']);

        // 'corge' should no longer be in the cache.
        $this->assertNull($inMemoryCache->get('corge'));

        // Add another new value, which should evict 'baz' from the cache.
        $inMemoryCache->set('grault', $values['grault']);

        // 'baz' should no longer exist in the cache.
        $this->assertNull($inMemoryCache->get('baz'));

        // The cache should contain 'qux', 'quux', and 'grault'.
        $this->assertSame(
            ['qux' => $values['qux'], 'quux' => $values['quux'], 'grault' => $values['grault']],
            iterator_to_array($inMemoryCache->getMultiple(['qux', 'quux', 'grault'])),
        );
    }

    public function testDefaultTtlIsTwoMinutes(): void
    {
        $clock = new class (new DateTimeImmutable('@1000')) implements ClockInterface {
            public function __construct(public DateTimeImmutable $time)
            {
            }

            public function now(): DateTimeImmutable
            {
                return $this->time;
            }
        };
        $inMemoryCache = new InMemoryCache(clock: $clock);
        $inMemoryCache->set('foo', 'bar');

        $clock->time = new DateTimeImmutable('@1119');
        $this->assertTrue($inMemoryCache->has('foo'));

        $clock->time = new DateTimeImmutable('@1120');
        $this->assertTrue($inMemoryCache->has('foo'));

        $clock->time = new DateTimeImmutable('@1121');
        $this->assertFalse($inMemoryCache->has('foo'));
    }

    public function testDefaultCacheSizeIsOneHundred(): void
    {
        $inMemoryCache = new InMemoryCache(clock: new FrozenClock(new DateTimeImmutable('@1000')));

        for ($i = 0; $i < 100; $i++) {
            $inMemoryCache->set("key$i", $i);
        }

        $this->assertTrue($inMemoryCache->has('key0'));

        $inMemoryCache->set('key100', 100);

        $this->assertFalse($inMemoryCache->has('key0'));
        $this->assertTrue($inMemoryCache->has('key1'));
        $this->assertTrue($inMemoryCache->has('key100'));
    }

    public function testTtlIsAddedToTheCurrentTime(): void
    {
        $clock = new class (new DateTimeImmutable('@1000')) implements ClockInterface {
            public function __construct(public DateTimeImmutable $time)
            {
            }

            public function now(): DateTimeImmutable
            {
                return $this->time;
            }
        };
        $inMemoryCache = new InMemoryCache(clock: $clock);
        $inMemoryCache->set('integer', 'value', 10);
        $inMemoryCache->set('interval', 'value', new DateInterval('PT10S'));

        $clock->time = new DateTimeImmutable('@1010');

        $this->assertTrue($inMemoryCache->has('integer'));
        $this->assertTrue($inMemoryCache->has('interval'));
    }

    public function testEvictionRemovesAnExpiredItemAndKeepsValidItems(): void
    {
        $clock = new class (new DateTimeImmutable('@1000')) implements ClockInterface {
            public function __construct(public DateTimeImmutable $time)
            {
            }

            public function now(): DateTimeImmutable
            {
                return $this->time;
            }
        };
        $inMemoryCache = new InMemoryCache(cacheSize: 2, clock: $clock);
        $inMemoryCache->set('expired', 'value', 1);
        $inMemoryCache->set('valid', 'value', 100);

        $clock->time = new DateTimeImmutable('@1002');
        $inMemoryCache->set('new', 'value', 100);

        $this->assertTrue($inMemoryCache->has('valid'));
        $this->assertTrue($inMemoryCache->has('new'));
    }

    public function testEvictionRemovesTheFirstItemWhenLastAccessTimesAreEqual(): void
    {
        $inMemoryCache = new InMemoryCache(
            cacheSize: 2,
            clock: new FrozenClock(new DateTimeImmutable('@1000')),
        );
        $inMemoryCache->set('first', 'value');
        $inMemoryCache->set('second', 'value');
        $inMemoryCache->set('third', 'value');

        $this->assertFalse($inMemoryCache->has('first'));
        $this->assertTrue($inMemoryCache->has('second'));
    }

    /**
     * @param callable(InMemoryCache): mixed $call
     */
    #[DataProvider('invalidArgumentProvider')]
    public function testInvalidArgumentsThrow(callable $call, string $message): void
    {
        $this->expectException(InvalidCacheArgument::class);
        $this->expectExceptionMessage($message);

        $call(new InMemoryCache());
    }

    /**
     * @return array<string, array{call: callable(InMemoryCache): mixed, message: string}>
     */
    public static function invalidArgumentProvider(): array
    {
        $key = 'The cache key must be a string; received int';
        $iterable = 'The cache keys or values must be iterable; received string';
        $ttl = 'The cache TTL must be null, an integer, or a DateInterval; received string';

        return [
            'get() with an integer key' => ['call' => static fn (InMemoryCache $c) => $c->get(123), 'message' => $key],
            'set() with an integer key' => [
                'call' => static fn (InMemoryCache $c) => $c->set(123, 'value'),
                'message' => $key,
            ],
            'set() with a string TTL' => [
                'call' => static fn (InMemoryCache $c) => $c->set('key', 'value', 'ttl'),
                'message' => $ttl,
            ],
            'delete() with an integer key' => [
                'call' => static fn (InMemoryCache $c) => $c->delete(123),
                'message' => $key,
            ],
            'has() with an integer key' => ['call' => static fn (InMemoryCache $c) => $c->has(123), 'message' => $key],
            'getMultiple() with a string' => [
                'call' => static fn (InMemoryCache $c) => $c->getMultiple('keys'),
                'message' => $iterable,
            ],
            'getMultiple() with an integer key, before iterating' => [
                'call' => static fn (InMemoryCache $c) => $c->getMultiple([123]),
                'message' => $key,
            ],
            'setMultiple() with a string' => [
                'call' => static fn (InMemoryCache $c) => $c->setMultiple('values'),
                'message' => $iterable,
            ],
            'setMultiple() with a string TTL' => [
                'call' => static fn (InMemoryCache $c) => $c->setMultiple(['key' => 'value'], 'ttl'),
                'message' => $ttl,
            ],
            'setMultiple() with a float key' => [
                'call' => static fn (InMemoryCache $c) => $c->setMultiple((static function () {
                    yield 1.5 => 'value';
                })()),
                'message' => 'The cache key must be a string; received float',
            ],
            'deleteMultiple() with a string' => [
                'call' => static fn (InMemoryCache $c) => $c->deleteMultiple('keys'),
                'message' => $iterable,
            ],
            'deleteMultiple() with an integer key' => [
                'call' => static fn (InMemoryCache $c) => $c->deleteMultiple([123]),
                'message' => $key,
            ],
        ];
    }

    public function testSetMultipleAcceptsKeysThatPhpStoresAsIntegers(): void
    {
        $inMemoryCache = new InMemoryCache();

        $this->assertTrue($inMemoryCache->setMultiple(['123' => 'value']));
        $this->assertSame('value', $inMemoryCache->get('123'));
    }

    public function testGetMultipleKeepsKeysThatLookLikeIntegersAsStrings(): void
    {
        $inMemoryCache = new InMemoryCache();
        $inMemoryCache->set('123', 'value');

        foreach ($inMemoryCache->getMultiple(['123']) as $key => $value) {
            $this->assertSame('123', $key);
            $this->assertSame('value', $value);
        }
    }

    public function testEvictionHandlesKeysThatLookLikeIntegers(): void
    {
        $inMemoryCache = new InMemoryCache(cacheSize: 1, clock: new FrozenClock(new DateTimeImmutable('@1000')));
        $inMemoryCache->set('123', 'first');
        $inMemoryCache->set('456', 'second');

        $this->assertFalse($inMemoryCache->has('123'));
        $this->assertSame('second', $inMemoryCache->get('456'));
    }
}
