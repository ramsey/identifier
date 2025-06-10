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

namespace Ramsey\Identifier\Service\Sequence;

/**
 * Derives a sequence value.
 *
 * Sequences may be ascending or descending, depending on the nature of the sequence. The `next()` method should always
 * return the next available value in the sequence, for the state provided.
 */
interface Sequence
{
    /**
     * Returns the current sequence value for the given state.
     *
     * @param non-empty-string | null $state If provided, the state is treated as a namespace from which the sequence
     *     value will be returned; each unique state has a difference sequence.
     */
    public function current(?string $state = null): int | string;

    /**
     * Advances the sequence and returns the next value for the given state.
     *
     * If the sequence has reached the maximum value allowed for the given state, it may throw a SequenceOverflow
     * exception.
     *
     * @param non-empty-string | null $state If provided, the state is treated as a namespace within which the next
     *     sequence value will be generated; each unique state has a different sequence.
     *
     * @throws SequenceOverflow when the sequence for a given state cannot be increased or decreased beyond its current
     *     value.
     */
    public function next(?string $state = null): int | string;
}
