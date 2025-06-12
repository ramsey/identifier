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
use Ramsey\Identifier\Service\Sequence\RandomSequence;

/**
 * A clock sequence that is randomly generated and does not use stable storage.
 */
final class RandomClockSequence implements ClockSequence
{
    private RandomSequence $sequence;

    public function __construct()
    {
        $this->sequence = new RandomSequence(min: 0);
    }

    public function current(?string $state = null, ?DateTimeInterface $dateTime = null): int
    {
        /** @var int<0, max> */
        return $this->sequence->current();
    }

    /**
     * **WARNING**: The next value in the sequence for {@see RandomClockSequence} is randomly generated. It is not
     * guaranteed to be a value greater than or less than the current value.
     *
     * {@inheritDoc}
     */
    public function next(?string $state = null, ?DateTimeInterface $dateTime = null): int
    {
        // Prevent against randomly generating the same as the current value.
        $current = $this->current();

        do {
            /** @var int<0, max> $next */
            $next = $this->sequence->next();
        } while ($next === $current);

        return $next;
    }
}
