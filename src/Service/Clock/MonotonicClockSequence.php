<?php

/**
 * This file is part of ramsey/identifier
 *
 * ramsey/identifier is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser
 * General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.
 *
 * ramsey/identifier is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the
 * implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License
 * for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License along with ramsey/identifier. If not, see
 * <https://www.gnu.org/licenses/>.
 *
 * @copyright Copyright (c) Ben Ramsey <ben@ramsey.dev> and Contributors
 * @license https://opensource.org/license/lgpl-3-0/ GNU Lesser General Public License version 3 or later
 */

declare(strict_types=1);

namespace Ramsey\Identifier\Service\Clock;

use DateTimeInterface;
use Psr\Clock\ClockInterface;
use Psr\SimpleCache\CacheInterface;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Service\Cache\InMemoryCache;
use Ramsey\Identifier\Service\Nic\Nic;
use Ramsey\Identifier\Service\Nic\RandomNic;

use function strlen;

use const PHP_INT_MAX;

/**
 * A clock sequence that always increases.
 *
 * @link https://en.wikipedia.org/wiki/Monotonic_function "Monotonic function" on Wikipedia.
 */
final class MonotonicClockSequence implements ClockSequence
{
    use GeneratorStateCache;

    /**
     * The cache key is generated from the Adler-32 checksum of this class name.
     *
     * ```
     * hash('adler32', MonotonicClockSequence::class);
     * ```
     */
    private const CACHE_KEY = '__ramsey_id_4cdb157d';

    private const STATE_ERROR_MESSAGE =
        'When getting the current or next clock sequence value, the state must be a non-empty string or null';

    /**
     * @var non-empty-string
     */
    private readonly string $defaultState;

    /**
     * @param int<0, max> | null $initialValue An initial clock sequence value; if not provided, it is randomly generated.
     * @param Nic $nic The system NIC, for maintaining state; defaults to {@see RandomNic}.
     * @param ClockInterface $clock A clock to use for determining state; defaults to {@see SystemClock}.
     * @param CacheInterface $cache A cache for storing the sequence and maintaining state.
     * @param Precision $precision The precision (i.e., millisecond or microsecond) to use when creating cache keys.
     */
    public function __construct(
        ?int $initialValue = null,
        Nic $nic = new RandomNic(),
        private readonly ClockInterface $clock = new SystemClock(),
        private readonly CacheInterface $cache = new InMemoryCache(),
        private readonly Precision $precision = Precision::Millisecond,
    ) {
        if ($initialValue !== null && $initialValue < 0) {
            throw new InvalidArgument('The clock sequence initial value must be a positive integer or null');
        }

        $this->initialValue = $initialValue;
        $this->defaultState = $nic->address();
    }

    public function current(?string $state = null, ?DateTimeInterface $dateTime = null): int
    {
        if ($state !== null && strlen($state) === 0) {
            throw new InvalidArgument(self::STATE_ERROR_MESSAGE);
        }

        return $this->getGeneratorState($state, $dateTime, false)->sequence;
    }

    public function next(?string $state = null, ?DateTimeInterface $dateTime = null): int
    {
        if ($state !== null && strlen($state) === 0) {
            throw new InvalidArgument(self::STATE_ERROR_MESSAGE);
        }

        return $this->getGeneratorState($state, $dateTime, true)->sequence;
    }

    /**
     * @param non-empty-string | null $state
     */
    private function getGeneratorState(?string $state, ?DateTimeInterface $dateTime, bool $increment): GeneratorState
    {
        $dateTime = $dateTime ?? $this->clock->now();
        $state = $state ?? $this->defaultState;

        $cacheKey = $this->getGeneratorStateCacheKey($state, $dateTime);
        $generatorState = $this->getGeneratorStateFromCache($cacheKey, $state, $dateTime);

        if ($increment) {
            // If the sequence is at the max value, roll it over to zero.
            if ($generatorState->sequence === PHP_INT_MAX) {
                $generatorState->sequence = 0;
            } else {
                $generatorState->sequence = $generatorState->sequence + 1;
            }

            $this->cache->set($cacheKey, $generatorState);
        }

        return $generatorState;
    }

    /**
     * @param non-empty-string $state
     *
     * @return non-empty-string
     */
    private function getGeneratorStateCacheKey(string $state, DateTimeInterface $dateTime): string
    {
        return self::CACHE_KEY . '|' . $state . '|' . $dateTime->format($this->precision->value);
    }
}
