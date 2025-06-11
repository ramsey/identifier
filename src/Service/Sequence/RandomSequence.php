<?php

/**
 * This file is part of ramsey/identifier
 *
 * ramsey/identifier is open source software: you can distribute it and/or modify it under the terms of the MIT License
 * (the "License"). You may not use this file except in compliance with the License.
 *
 * @copyright Copyright (c) Ben Ramsey <ben@benramsey.com>
 * @license https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Ramsey\Identifier\Service\Sequence;

use function random_int;

use const PHP_INT_MAX;
use const PHP_INT_MIN;

/**
 * Uses PHP's `random_int()` function to generate a random sequence value.
 *
 * > [!CAUTION]
 * > Values generated using RandomSequence are not sequential or monotonic. They may be positive or negative integers.
 */
final class RandomSequence implements Sequence
{
    private int $current;

    /**
     * @param int $min The minimum value allowed in this random sequence (inclusive).
     * @param int $max The maximum value allowed in this random sequence (inclusive).
     */
    public function __construct(
        private readonly int $min = PHP_INT_MIN,
        private readonly int $max = PHP_INT_MAX,
    ) {
        // Initialize the random sequence.
        $this->current = random_int($this->min, $this->max);
    }

    public function current(?string $state = null): int
    {
        return $this->current;
    }

    public function next(?string $state = null): int
    {
        return $this->current = random_int($this->min, $this->max);
    }
}
