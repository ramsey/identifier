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

namespace Ramsey\Identifier\Service\Clock;

use DateTimeInterface;

use function random_int;

use const PHP_INT_MAX;

trait GeneratorStateCache
{
    /**
     * @var int<0, max> | null
     */
    private readonly ?int $initialValue;

    private bool $initialValueUsed = false;

    /**
     * @param non-empty-string $state
     */
    private function getGeneratorStateFromCache(
        string $cacheKey,
        string $state,
        DateTimeInterface $dateTime,
    ): GeneratorState {
        $generatorState = $this->cache->get($cacheKey);

        if ($generatorState === null) {
            $generatorState = new GeneratorState(
                node: $state,
                sequence: $this->initializeValue(),
                timestamp: (int) $dateTime->format(Precision::Microsecond->value),
            );
        }

        if (!$generatorState instanceof GeneratorState) {
            throw new InvalidGeneratorState('The generator state must be an instance of ' . GeneratorState::class);
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
