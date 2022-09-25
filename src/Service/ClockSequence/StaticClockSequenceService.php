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
 * A clock sequence service that provides a static integer, primarily for
 * deterministic testing purposes
 */
final class StaticClockSequenceService implements ClockSequenceServiceInterface
{
    /**
     * @param int<0, 16383> $clockSequence
     */
    public function __construct(private readonly int $clockSequence)
    {
    }

    public function getClockSequence(): int
    {
        return $this->clockSequence;
    }
}
