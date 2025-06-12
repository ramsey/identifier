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

namespace Ramsey\Identifier\Service\Clock;

use DateTimeInterface;
use Ramsey\Identifier\Exception\InvalidArgument;

/**
 * A clock sequence that always returns the same pre-determined value. Calling `next()` does not advance the sequence.
 */
final readonly class FrozenClockSequence implements ClockSequence
{
    /**
     * @param int<0, max> $value A pre-determined sequence value.
     */
    public function __construct(private int $value)
    {
        if ($this->value < 0) {
            throw new InvalidArgument('$value must be a non-negative integer');
        }
    }

    public function current(?string $state = null, ?DateTimeInterface $dateTime = null): int
    {
        return $this->value;
    }

    /**
     * **WARNING**: The clock sequence does not advance for {@see FrozenClockSequence}s.
     *
     * {@inheritDoc}
     */
    public function next(?string $state = null, ?DateTimeInterface $dateTime = null): int
    {
        return $this->value;
    }
}
