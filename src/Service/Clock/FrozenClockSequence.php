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

/**
 * A clock sequence that returns a pre-determined value as the sequence and never changes.
 */
final readonly class FrozenClockSequence implements ClockSequence
{
    /**
     * @param int<0, max> $value A pre-determined clock sequence value
     */
    public function __construct(private int $value)
    {
    }

    public function current(?string $state = null, ?DateTimeInterface $dateTime = null): int
    {
        return $this->value;
    }

    public function next(?string $state = null, ?DateTimeInterface $dateTime = null): int
    {
        return $this->value;
    }
}
