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

use function random_int;

use const PHP_INT_MAX;

/**
 * A utility for fetching the generator state from the cache.
 *
 * @internal Not intended for use outside ramsey/identifier; may change without notice.
 */
trait GeneratorStateCache
{
    /**
     * @var int<0, max> | null
     */
    private readonly ?int $initialValue;

    /**
     * Whether the generator has already used the initial value. If the initial value has been used, then the next time
     * we generate a new sequence value, we will randomly select a value instead of starting over with the initial value.
     * This can occur when a clock sequence rolls over or the generator state cannot be found in the cache.
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
                timestamp: (int) $dateTime->format(Precision::Microsecond->value),
            );

            $this->cache->set($cacheKey, $generatorState);
        }

        return $generatorState;
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
