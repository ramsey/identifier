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
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Service\Cache\InMemoryCache;

use function spl_object_hash;

use const PHP_INT_MAX;

/**
 * An integer sequence that always increases by a given step value
 */
final class IncreasingSequence implements Sequence
{
    private const CACHE_KEY = '__ramsey_id_increasing_sequence';

    private ?string $internalCacheKey = null;

    /**
     * @param int $start The sequence starting value; please note, the first call to
     *     `next()` will return this value + `$step`
     * @param positive-int $step How much the sequence should increase between values
     * @param CacheInterface $cache A cache for storing the sequence and maintaining state
     */
    public function __construct(
        private readonly int $start = 0,
        private readonly int $step = 1,
        private readonly CacheInterface $cache = new InMemoryCache(),
    ) {
        if ($step <= 0) {
            throw new InvalidArgument('Step must be a positive integer');
        }
    }

    public function next(?string $state = null): int
    {
        $cacheKey = $this->generateCacheKey($state);

        /** @var int $previous */
        $previous = $this->cache->get($cacheKey, $this->start);

        if ($previous === PHP_INT_MAX || (PHP_INT_MAX - $previous) < $this->step) {
            throw new SequenceOverflow('Unable to increase sequence beyond its maximum value');
        }

        $next = $previous + $this->step;
        $this->cache->set($cacheKey, $next);

        return $next;
    }

    private function generateCacheKey(?string $state): string
    {
        if ($state === null) {
            if ($this->internalCacheKey === null) {
                $this->internalCacheKey = self::CACHE_KEY
                    . ":start={$this->start};step={$this->step}:"
                    . spl_object_hash($this);
            }

            return $this->internalCacheKey;
        }

        return self::CACHE_KEY . ':' . $state;
    }
}
