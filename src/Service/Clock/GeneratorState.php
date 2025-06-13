<?php

/**
 * This file is part of ramsey/identifier
 *
 * ramsey/identifier is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser
 * General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.
 *
 * ramsey/identifier is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the
 * implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License
 * for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License along with ramsey/identifier. If not, see
 * <https://www.gnu.org/licenses/>.
 *
 * @copyright Copyright (c) Ben Ramsey <ben@ramsey.dev> and Contributors
 * @license https://opensource.org/license/lgpl-3-0/ GNU Lesser General Public License version 3 or later
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
            throw new InvalidArgument('The generator state node must be a non-empty string');
        }

        if ($this->sequence < 0) {
            throw new InvalidArgument('The generator state sequence must be a positive integer');
        }
    }
}
