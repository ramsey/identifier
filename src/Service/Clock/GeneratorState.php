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

use Ramsey\Identifier\Exception\InvalidArgument;

use function strlen;

/**
 * A value object for storing and passing the generator state for clock sequences.
 *
 * @internal Not intended for use outside ramsey/identifier; may change without notice.
 */
final class GeneratorState
{
    /**
     * @param non-empty-string $node
     * @param int<0, max> $sequence
     */
    public function __construct(
        public string $node,
        public int $sequence,
        public int $timestamp,
    ) {
        if (strlen($this->node) === 0) {
            throw new InvalidArgument('$node must be a non-empty string');
        }

        if ($this->sequence < 0) {
            throw new InvalidArgument('$sequence must be a non-negative integer');
        }
    }
}
