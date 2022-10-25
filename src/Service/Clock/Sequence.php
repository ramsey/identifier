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
 * Defines a clock sequence interface for obtaining a clock sequence value, for
 * the purpose of avoiding duplicates or collisions
 */
interface Sequence
{
    /**
     * Calculates and returns a clock sequence value
     *
     * @param int | non-empty-string $node A value that identifies the machine
     *     or node; this may be the MAC address, or it may be some other
     *     identifier, according to the application's need; the Sequence should
     *     compare this node to a previously-stored node and, if it has changed,
     *     regenerate the clock sequence value
     * @param DateTimeInterface $dateTime A date-time value for comparison to a
     *     previously-stored date-time value; if this value is less than or
     *     equal to the previous value, then the Sequence should increment the
     *     clock sequence value, since the clock has been set backwards
     *
     * @return int<0, max>
     */
    public function value(int | string $node, DateTimeInterface $dateTime): int;
}
