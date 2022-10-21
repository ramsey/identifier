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
 * A sequence that returns a pre-determined value as the clock sequence
 */
final class FrozenSequence implements Sequence
{
    /**
     * @param int<0, max> $value A pre-determined clock sequence value
     */
    public function __construct(private readonly int $value)
    {
    }

    public function value(string $node, DateTimeInterface $dateTime): int
    {
        return $this->value;
    }
}
