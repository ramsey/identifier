<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Service\Cache;

use DateInterval;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\TestDox;
use PHPUnit\Framework\TestCase;
use Ramsey\Identifier\Service\Cache\InMemoryCache;
use stdClass;

use function array_keys;
use function array_slice;
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

        // Access 'foo' next, so it's the second least most recently accessed item.
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

        // Add 'corge', but give it a TTL in the past, so it's considered invalid
        // and will be evicted before 'baz'.
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
}
