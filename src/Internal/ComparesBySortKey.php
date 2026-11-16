<?php

/**
 * This file is part of ramsey/identifier
 *
 * Copyright © Ben Ramsey <ben@ramsey.dev> and Contributors
 *
 * ramsey/identifier is free software: you can redistribute it and/or modify it
 * under the terms of the GNU Lesser General Public License as published by the
 * Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.
 *
 * ramsey/identifier is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License
 * for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with ramsey/identifier. If not, see <https://www.gnu.org/licenses/>.
 *
 * SPDX-License-Identifier: LGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Ramsey\Identifier\Internal;

use Identifier\Identifier;
use Ramsey\Identifier\Exception\NotComparable;
use Stringable;
use Throwable;

use function get_debug_type;
use function is_string;
use function sprintf;
use function strcmp;

/**
 * Compares identifiers by their sort keys
 *
 * An identifier compares with any other identifier by sort key. It compares
 * with a string, or with a Stringable object that is not an identifier, only
 * when the string is in the identifier's own string form. It is equal only to
 * an identifier of the same kind with the same sort key, or to a string in its
 * own string form with the same value.
 *
 * @internal Not intended for use outside ramsey/identifier; may change without
 *     notice.
 */
trait ComparesBySortKey
{
    /**
     * @throws NotComparable when the other value is not an identifier or a
     *     string in the identifier's own string form
     */
    public function compareTo(mixed $other): int
    {
        if ($other instanceof Identifier) {
            return strcmp($this->getSortKey(), $other->getSortKey());
        }

        if ($other instanceof Stringable) {
            try {
                $other = (string) $other;
            } catch (Throwable $exception) {
                throw new NotComparable(
                    sprintf('Comparison with values of type "%s" is not supported', get_debug_type($other)),
                    previous: $exception,
                );
            }
        }

        if (is_string($other)) {
            $sortKey = $this->sortKeyFromString($other);

            if ($sortKey !== null) {
                return strcmp($this->getSortKey(), $sortKey);
            }

            throw new NotComparable(sprintf('Comparison with the string "%s" is not supported', $other));
        }

        throw new NotComparable(
            sprintf('Comparison with values of type "%s" is not supported', get_debug_type($other)),
        );
    }

    public function equals(mixed $other): bool
    {
        if ($other instanceof Identifier) {
            return $this->isSameKind($other) && $this->getSortKey() === $other->getSortKey();
        }

        if ($other instanceof Stringable) {
            try {
                $other = (string) $other;
            } catch (Throwable) {
                return false;
            }
        }

        if (is_string($other)) {
            return $this->sortKeyFromString($other) === $this->getSortKey();
        }

        return false;
    }

    /**
     * Returns a string of bytes that places the identifier in order among other
     * identifiers
     *
     * @return non-empty-string
     */
    abstract public function getSortKey(): string;

    /**
     * Returns true if the other identifier is of the same kind as this one
     */
    abstract private function isSameKind(Identifier $other): bool;

    /**
     * Returns the sort key an identifier of this kind created from the value
     * would have, or null when the value is not in the accepted string form
     *
     * @return non-empty-string | null
     */
    abstract private function sortKeyFromString(string $value): ?string;
}
