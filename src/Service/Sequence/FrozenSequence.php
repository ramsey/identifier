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

/**
 * A sequence that always returns the same pre-determined value. Calling `next()` does not advance the sequence.
 */
final readonly class FrozenSequence implements Sequence
{
    /**
     * @param int | string $value A pre-determined sequence value.
     */
    public function __construct(private int | string $value)
    {
    }

    public function current(?string $state = null): int | string
    {
        return $this->value;
    }

    /**
     * **WARNING**: The sequence does not advance for {@see FrozenSequence}s.
     *
     * {@inheritDoc}
     */
    public function next(?string $state = null): int | string
    {
        return $this->value;
    }
}
