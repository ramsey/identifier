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
 * Calculates the clock sequence value, according to the algorithm defined in RFC 4122.
 *
 * RFC 4122, section 4.2.1 defines a basic algorithm for generating a clock sequence. It describes the algorithm as
 * "simple, correct, and inefficient."
 *
 * > * Obtain a system-wide global lock
 * > * From a system-wide shared stable store (e.g., a file), read the UUID generator state: the values of the timestamp,
 * >   clock sequence, and node ID used to generate the last UUID.
 * > * Get the current time as a 60-bit count of 100-nanosecond intervals since 00:00:00.00, 15 October 1582.
 * > * Get the current node ID.
 * > * If the state was unavailable (e.g., non-existent or corrupted), or the saved node ID is different than the current
 * >   node ID, generate a random clock sequence value.
 * > * If the state was available, but the saved timestamp is later than the current timestamp, increment the clock
 * >   sequence value.
 * > * Save the state (current timestamp, clock sequence, and node ID) back to the stable store.
 * > * Release the global lock.
 *
 * The implementation in this class deviates from the algorithm in a few notable ways:
 *
 * * It does not acquire a system-wide global lock.
 * * The system-wide shared stable store is possible through use of a cache, which you may provide to the constructor.
 * * This implementation uses microsecond time precision instead of 100-nanosecond interval time precision.
 *
 * WARNING: Since this does not acquire a global lock, race conditions could occur when fetching or storing data to the
 * cache. If needed, you may create your own cache implementation that resolves these shortcomings.
 *
 * NOTE: The algorithm defined in RFC 4122 was not included in RFC 9562.
 *
 * @link https://www.rfc-editor.org/rfc/rfc4122#section-4.2.1 RFC 4122, 4.2.1. Basic Algorithm
 */
final class Rfc4122ClockSequence implements ClockSequence
{
    use GeneratorStateCache;

    /**
     * The cache key is generated from the Adler-32 checksum of this class name.
     *
     * ```
     * hash('adler32', Rfc4122ClockSequence::class);
     * ```
     */
    private const CACHE_KEY = '__ramsey_id_122f13ab';

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
     */
    public function __construct(
        ?int $initialValue = null,
        Nic $nic = new RandomNic(),
        private readonly ClockInterface $clock = new SystemClock(),
        private readonly CacheInterface $cache = new InMemoryCache(),
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

        return $this->getGeneratorState($state, $dateTime)->sequence;
    }

    public function next(?string $state = null, ?DateTimeInterface $dateTime = null): int
    {
        if ($state !== null && strlen($state) === 0) {
            throw new InvalidArgument(self::STATE_ERROR_MESSAGE);
        }

        return $this->getGeneratorState($state, $dateTime)->sequence;
    }

    /**
     * @param non-empty-string | null $state
     */
    private function getGeneratorState(?string $state, ?DateTimeInterface $dateTime): GeneratorState
    {
        $dateTime = $dateTime ?? $this->clock->now();
        $state = $state ?? $this->defaultState;

        $cacheKey = $this->getGeneratorStateCacheKey($state);
        $generatorState = $this->getGeneratorStateFromCache($cacheKey, $state, $dateTime);

        // If the state timestamp is later than the current, the clock was set backward, and we must increment the sequence.
        if ($generatorState->timestamp > (int) $dateTime->format(Precision::Microsecond->value)) {
            // If the sequence is at the max value, roll it over to zero.
            if ($generatorState->sequence === PHP_INT_MAX) {
                $generatorState->sequence = 0;
            } else {
                $generatorState->sequence++;
            }

            $generatorState->timestamp = (int) $dateTime->format(Precision::Microsecond->value);
            $this->cache->set($cacheKey, $generatorState);
        }

        return $generatorState;
    }

    /**
     * @param non-empty-string $state
     *
     * @return non-empty-string
     */
    private function getGeneratorStateCacheKey(string $state): string
    {
        return self::CACHE_KEY . '|' . $state;
    }
}
