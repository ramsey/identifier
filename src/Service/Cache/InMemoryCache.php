<?php

/**
 * This file is part of ramsey/identifier
 *
 * Copyright © Ben Ramsey <ben@ramsey.dev> and Contributors
 *
 * ramsey/identifier is free software: you can redistribute it and/or modify it
 * under the terms of the GNU Lesser General Public License as published by the
 * Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.
 *
 * ramsey/identifier is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License
 * for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with ramsey/identifier. If not, see <https://www.gnu.org/licenses/>.
 *
 * SPDX-License-Identifier: LGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Ramsey\Identifier\Service\Cache;

use DateInterval;
use DateTimeImmutable;
use Override;
use Psr\Clock\ClockInterface;
use Psr\SimpleCache\CacheInterface;
use Ramsey\Identifier\Service\Clock\SystemClock;

use function count;
use function get_debug_type;
use function is_int;
use function is_iterable;
use function is_string;
use function sprintf;

/**
 * A simple PSR-16 in-memory cache implementation
 *
 * Code and concepts within this class are borrowed from the
 * marvin255/in-memory-cache package and are used under the terms of the MIT
 * license distributed with marvin255/in-memory-cache.
 *
 * marvin255/in-memory-cache is copyright (c) liquetsoft.
 *
 * @link https://github.com/marvin255/in-memory-cache marvin255/in-memory-cache
 * @link https://github.com/marvin255/in-memory-cache/blob/v2.3.3/LICENSE MIT License
 */
final class InMemoryCache implements CacheInterface
{
    /**
     * @var array<array-key, CacheItem>
     */
    private array $cache = [];

    /**
     * @param int $defaultTtl The default number of seconds each cache item is
     *     valid
     * @param int $cacheSize The number of items allowed in the cache at a given
     *     time
     * @param ClockInterface $clock A clock to use for determining freshness of
     *     cache items
     */
    public function __construct(
        private readonly int $defaultTtl = 120,
        private readonly int $cacheSize = 100,
        private readonly ClockInterface $clock = new SystemClock(),
    ) {
    }

    /**
     * @param mixed $key The key of the item, which must be a string
     * @param mixed $default The value to return if the key does not exist
     *
     * @throws InvalidCacheArgument if the key is not a string
     */
    #[Override]
    public function get(mixed $key, mixed $default = null): mixed
    {
        $this->checkKey($key);

        $cacheItem = $this->cache[$key] ?? null;

        if ($this->isValid($key, $cacheItem)) {
            $cacheItem->lastAccess = $this->clock->now();

            return $cacheItem->value;
        }

        return $default;
    }

    /**
     * @param mixed $key The key of the item, which must be a string
     * @param mixed $value The value to store
     * @param mixed $ttl The number of seconds the item is valid, as an integer
     *     or a DateInterval, or null for the default
     *
     * @throws InvalidCacheArgument if the key or the TTL is not a valid type
     */
    #[Override]
    public function set(mixed $key, mixed $value, mixed $ttl = null): bool
    {
        $this->checkKey($key);
        $this->checkTtl($ttl);

        if (count($this->cache) >= $this->cacheSize) {
            // make room for a new value.
            $this->evictCacheItem();
        }

        $this->cache[$key] = new CacheItem(
            $value,
            $this->createTtl($ttl),
            $this->clock->now(),
        );

        return true;
    }

    /**
     * @param mixed $key The key of the item, which must be a string
     *
     * @throws InvalidCacheArgument if the key is not a string
     */
    #[Override]
    public function delete(mixed $key): bool
    {
        $this->checkKey($key);

        unset($this->cache[$key]);

        return true;
    }

    #[Override]
    public function clear(): bool
    {
        $this->cache = [];

        return true;
    }

    /**
     * @param mixed $keys An iterable of string keys
     * @param mixed $default The value to return for a key that does not exist
     *
     * @return iterable<string, mixed>
     *
     * @throws InvalidCacheArgument if the keys are not iterable, or if a key
     *     is not a string
     */
    #[Override]
    public function getMultiple(mixed $keys, mixed $default = null): iterable
    {
        $this->checkIterable($keys);

        $items = [];

        foreach ($keys as $key) {
            $this->checkKey($key);
            $items[] = [$key, $this->get($key, $default)];
        }

        return $this->yieldItems($items);
    }

    /**
     * @param mixed $values An iterable of values, indexed by their keys
     * @param mixed $ttl The number of seconds the items are valid, as an
     *     integer or a DateInterval, or null for the default
     *
     * @throws InvalidCacheArgument if the values are not iterable, if a key is
     *     not a string or an integer, or if the TTL is not a valid type
     */
    #[Override]
    public function setMultiple(mixed $values, mixed $ttl = null): bool
    {
        $this->checkIterable($values);
        $this->checkTtl($ttl);

        foreach ($values as $key => $value) {
            // PHP stores an array key such as "123" as an integer.
            $this->set(is_int($key) ? (string) $key : $key, $value, $ttl);
        }

        return true;
    }

    /**
     * @param mixed $keys An iterable of string keys
     *
     * @throws InvalidCacheArgument if the keys are not iterable, or if a key
     *     is not a string
     */
    #[Override]
    public function deleteMultiple(mixed $keys): bool
    {
        $this->checkIterable($keys);

        foreach ($keys as $key) {
            $this->delete($key);
        }

        return true;
    }

    /**
     * @param mixed $key The key of the item, which must be a string
     *
     * @throws InvalidCacheArgument if the key is not a string
     */
    #[Override]
    public function has(mixed $key): bool
    {
        $this->checkKey($key);

        return $this->isValid($key, $this->cache[$key] ?? null);
    }

    /**
     * @throws InvalidCacheArgument
     *
     * @phpstan-assert string $key
     */
    private function checkKey(mixed $key): void
    {
        if (!is_string($key)) {
            throw new InvalidCacheArgument(
                sprintf('The cache key must be a string; received %s', get_debug_type($key)),
            );
        }
    }

    /**
     * @throws InvalidCacheArgument
     *
     * @phpstan-assert iterable<mixed, mixed> $value
     */
    private function checkIterable(mixed $value): void
    {
        if (!is_iterable($value)) {
            throw new InvalidCacheArgument(
                sprintf('The cache keys or values must be iterable; received %s', get_debug_type($value)),
            );
        }
    }

    /**
     * @throws InvalidCacheArgument
     *
     * @phpstan-assert DateInterval | int | null $ttl
     */
    private function checkTtl(mixed $ttl): void
    {
        if ($ttl !== null && !is_int($ttl) && !$ttl instanceof DateInterval) {
            throw new InvalidCacheArgument(sprintf(
                'The cache TTL must be null, an integer, or a DateInterval; received %s',
                get_debug_type($ttl),
            ));
        }
    }

    /**
     * @param list<array{string, mixed}> $items
     *
     * @return iterable<string, mixed>
     */
    private function yieldItems(array $items): iterable
    {
        foreach ($items as [$key, $value]) {
            yield $key => $value;
        }
    }

    /**
     * Returns a Unix timestamp representing the expiration time of an item in
     * the cache
     */
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
     * Returns true if the cache item is valid, taking into account its TTL
     *
     * An item with an expired TTL is invalid.
     *
     * @phpstan-assert-if-true CacheItem $cacheItem
     */
    private function isValid(string $key, ?CacheItem $cacheItem): bool
    {
        if ($cacheItem === null) {
            return false;
        }

        $isValid = $cacheItem->ttl >= $this->clock->now()->getTimestamp();

        if ($isValid === false) {
            // If it's no longer valid, evict it from the cache.
            $this->delete($key);
        }

        return $isValid;
    }

    /**
     * Attempts to remove an invalid cache item first, and if it can't find one,
     * it resorts to removing the least recently used cache item
     */
    private function evictCacheItem(): void
    {
        $keyToRemove = null;
        $leastRecentlyUsed = null;

        foreach ($this->cache as $arrayKey => $cacheItem) {
            // PHP stores an array key such as "123" as an integer.
            $key = (string) $arrayKey;

            if (!$this->isValid($key, $cacheItem)) {
                $keyToRemove = $key;

                break;
            }

            $lastAccess = $cacheItem->lastAccess;
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
