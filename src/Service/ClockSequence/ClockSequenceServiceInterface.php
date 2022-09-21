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

namespace Ramsey\Identifier\Service\ClockSequence;

/**
 * Defines a service interface for obtaining a clock sequence used with version
 * 1 and 6 UUIDs
 */
interface ClockSequenceServiceInterface
{
    /**
     * Returns a clock sequence used to help avoid duplicates/collisions that
     * could occur if the clock is set backwards or if the node changes
     *
     * @return int<0, 16383>
     */
    public function getClockSequence(): int;
}
