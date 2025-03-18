<?php

declare(strict_types=1);

namespace Ramsey\Identifier\Service\Cache;

use DateInterval;
use DateTimeImmutable;
use Psr\Clock\ClockInterface;
use Psr\SimpleCache\CacheInterface;
use Ramsey\Identifier\Service\Clock\SystemClock;

use function count;

/**
 * A simple PSR-16 in-memory cache implementation
 *
 * Code and concepts within this class are borrowed from the marvin255/in-memory-cache
 * package and are used under the terms of the MIT license distributed with
 * marvin255/in-memory-cache.
 *
 * marvin255/in-memory-cache is copyright (c) liquetsoft.
 *
 * @link https://github.com/marvin255/in-memory-cache marvin255/in-memory-cache
 * @link https://github.com/marvin255/in-memory-cache/blob/v2.3.3/LICENSE MIT License
 *
 * @phpstan-type CacheItem array{value: mixed, ttl: int, last_access: int}
 */
final class InMemoryCache implements CacheInterface
{
    /**
     * @var array<string, CacheItem>
     */
    private array $cache = [];

    /**
     * @param int $defaultTtl The default number of seconds each cache item is valid.
     * @param int $cacheSize The number of items allowed in the cache at a given time.
     * @param ClockInterface $clock A clock to use for determining freshness of cache items.
     */
    public function __construct(
        private readonly int $defaultTtl = 300,
        private readonly int $cacheSize = 500,
        private readonly ClockInterface $clock = new SystemClock(),
    ) {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $cacheItem = $this->cache[$key] ?? null;

        if ($this->isValid($key, $cacheItem)) {
            $this->cache[$key]['last_access'] = (int) $this->clock->now()->format('Uu');

            return $cacheItem['value'];
        }

        return $default;
    }

    public function set(string $key, mixed $value, DateInterval | int | null $ttl = null): bool
    {
        if (count($this->cache) >= $this->cacheSize) {
            // make room for a new value.
            $this->evictCacheItem();
        }

        $this->cache[$key] = [
            'value' => $value,
            'ttl' => $this->createTtl($ttl),
            'last_access' => (int) $this->clock->now()->format('Uu'),
        ];

        return true;
    }

    public function delete(string $key): bool
    {
        unset($this->cache[$key]);

        return true;
    }

    public function clear(): bool
    {
        $this->cache = [];

        return true;
    }

    /**
     * @inheritDoc
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        foreach ($keys as $key) {
            yield $key => $this->get($key, $default);
        }
    }

    /**
     * @param iterable<string, mixed> $values
     *
     * @inheritDoc
     */
    public function setMultiple(iterable $values, DateInterval | int | null $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }

        return true;
    }

    public function has(string $key): bool
    {
        return $this->isValid($key, $this->cache[$key] ?? null);
    }

    private function createTtl(DateInterval | int | null $ttl): int
    {
        $timestamp = $this->clock->now()->getTimestamp();

        if ($ttl === null) {
            $timestamp += $this->defaultTtl;
        } elseif ($ttl instanceof DateInterval) {
            $timestamp += $this->getSecondsInInterval($ttl);
        } else {
            $timestamp += $ttl;
        }

        return $timestamp;
    }

    /**
     * @param CacheItem | null $cacheItem
     *
     * @phpstan-assert-if-true CacheItem $cacheItem
     */
    private function isValid(string $key, ?array $cacheItem): bool
    {
        if ($cacheItem === null) {
            return false;
        }

        $isValid = $cacheItem['ttl'] >= $this->clock->now()->getTimestamp();

        if ($isValid === false) {
            // If it's no longer valid, evict it from the cache.
            $this->delete($key);
        }

        return $isValid;
    }

    /**
     * Attempts to remove an invalid cache item first, and if it can't find one,
     * it resorts to removing the least recently used cache item.
     */
    private function evictCacheItem(): void
    {
        $keyToRemove = null;
        $leastRecentlyUsed = null;

        foreach ($this->cache as $key => $cacheItem) {
            if (!$this->isValid($key, $cacheItem)) {
                $keyToRemove = $key;

                break;
            }

            $lastAccess = $cacheItem['last_access'];
            if ($leastRecentlyUsed === null || $lastAccess < $leastRecentlyUsed) {
                $keyToRemove = $key;
                $leastRecentlyUsed = $lastAccess;
            }
        }

        if ($keyToRemove !== null) {
            $this->delete($keyToRemove);
        }
    }

    private function getSecondsInInterval(DateInterval $interval): int
    {
        $reference = new DateTimeImmutable();
        $endTime = $reference->add($interval);

        return $endTime->getTimestamp() - $reference->getTimestamp();
    }
}
