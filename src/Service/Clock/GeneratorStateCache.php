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

namespace Ramsey\Identifier\Service\Clock;

use DateTimeInterface;

use function intdiv;
use function random_int;

use const PHP_INT_MAX;
use const PHP_INT_MIN;

/**
 * A utility for fetching the generator state from the cache
 *
 * @internal Not intended for use outside ramsey/identifier; may change without
 *     notice.
 */
trait GeneratorStateCache
{
    private const MICROSECONDS_PER_SECOND = 1_000_000;

    /**
     * @var int<0, max> | null
     */
    private readonly ?int $initialValue;

    /**
     * Whether the generator has already used the initial value
     *
     * If the initial value has been used, then the next time we generate a new
     * sequence value, we will randomly select a value instead of starting over
     * with the initial value. This can occur when a clock sequence rolls over
     * or the generator state cannot be found in the cache.
     */
    private bool $initialValueUsed = false;

    /**
     * @param non-empty-string $cacheKey
     * @param non-empty-string $state
     */
    private function getGeneratorStateFromCache(
        string $cacheKey,
        string $state,
        DateTimeInterface $dateTime,
    ): GeneratorState {
        $generatorState = $this->cache->get($cacheKey);

        if ($generatorState !== null && !$generatorState instanceof GeneratorState) {
            throw new InvalidGeneratorState('The generator state must be an instance of ' . GeneratorState::class);
        }

        if ($generatorState === null) {
            $generatorState = new GeneratorState(
                node: $state,
                sequence: $this->initializeValue(),
                timestamp: $this->getUnixMicroseconds($dateTime),
            );

            $this->cache->set($cacheKey, $generatorState);
        }

        return $generatorState;
    }

    /**
     * Returns the number of microseconds from the Unix Epoch to the date-time,
     * which is negative for a date-time before the Unix Epoch
     *
     * A date-time too far from the Unix Epoch to count in microseconds as an
     * int returns the largest or the smallest int. So does a date-time in the
     * last second that an int can count.
     */
    private function getUnixMicroseconds(DateTimeInterface $dateTime): int
    {
        $seconds = $dateTime->getTimestamp();

        if ($seconds > intdiv(PHP_INT_MAX, self::MICROSECONDS_PER_SECOND) - 1) {
            return PHP_INT_MAX;
        }

        if ($seconds < intdiv(PHP_INT_MIN, self::MICROSECONDS_PER_SECOND)) {
            return PHP_INT_MIN;
        }

        // The timestamp is whole seconds rounded down, and the fraction is
        // never negative, so for a date-time before the Unix Epoch the two
        // add up to the right number of microseconds.
        return $seconds * self::MICROSECONDS_PER_SECOND + (int) $dateTime->format('u');
    }

    /**
     * @return int<0, max>
     */
    private function initializeValue(): int
    {
        if ($this->initialValue !== null && !$this->initialValueUsed) {
            $this->initialValueUsed = true;

            return $this->initialValue;
        }

        return random_int(0, PHP_INT_MAX);
    }
}
