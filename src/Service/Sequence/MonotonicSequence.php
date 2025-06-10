<?php

/**
 * This file is part of ramsey/identifier
 *
 * ramsey/identifier is open source software: you can distribute
 * it and/or modify it under the terms of the MIT License
 * (the "License"). You may not use this file except in
 * compliance with the License.
 *
 * @copyright Copyright (c) Ben Ramsey <ben@benramsey.com>
 * @license https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Ramsey\Identifier\Service\Sequence;

use Psr\SimpleCache\CacheInterface;
use Ramsey\Identifier\Service\Cache\InMemoryCache;

use function abs;
use function spl_object_hash;

use const PHP_INT_MAX;
use const PHP_INT_MIN;

/**
 * An integer sequence that always increases or decreases by a given step value.
 *
 * If the step value is positive, the sequence will be *monotonically increasing*. If the step value is negative, the
 * sequence will be *monotonically decreasing*.
 *
 * @link https://en.wikipedia.org/wiki/Monotonic_function "Monotonic function" on Wikipedia
 */
final class MonotonicSequence implements Sequence
{
    /**
     * The cache key is generated from the Adler-32 checksum of this class name.
     *
     * ```
     * hash('adler32', MonotonicSequence::class);
     * ```
     */
    private const CACHE_KEY = '__ramsey_id_259414de';

    private ?string $defaultCacheKey = null;

    /**
     * @param int $start The sequence starting value; please note, the first call to `next()` will return this value + `$step`.
     * @param int $step How much the sequence should increase or decrease between values.
     * @param CacheInterface $cache A cache for storing the sequence and maintaining state.
     */
    public function __construct(
        private readonly int $start = 0,
        private readonly int $step = 1,
        private readonly CacheInterface $cache = new InMemoryCache(),
    ) {
    }

    public function current(?string $state = null): int
    {
        /** @var int */
        return $this->cache->get($this->generateCacheKey($state), $this->start);
    }

    public function next(?string $state = null): int
    {
        $previous = $this->current($state);

        if ($this->step > 0 && $previous === PHP_INT_MAX || (PHP_INT_MAX - $previous) < $this->step) {
            throw new SequenceOverflow('Unable to increase sequence beyond its maximum value');
        } elseif ($this->step < 0 && $previous === PHP_INT_MIN || ($previous - PHP_INT_MIN) < abs($this->step)) {
            throw new SequenceOverflow('Unable to decrease sequence beyond its minimum value');
        }

        $next = $previous + $this->step;
        $this->cache->set($this->generateCacheKey($state), $next);

        return $next;
    }

    private function generateCacheKey(?string $state): string
    {
        if ($state === null) {
            if ($this->defaultCacheKey === null) {
                $this->defaultCacheKey = self::CACHE_KEY
                    . "|start={$this->start};step={$this->step}|"
                    . spl_object_hash($this);
            }

            return $this->defaultCacheKey;
        }

        return self::CACHE_KEY . '|' . $state;
    }
}
