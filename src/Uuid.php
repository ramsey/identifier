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

namespace Ramsey\Identifier;

use Identifier\BytesIdentifier;
use Identifier\IntegerIdentifier;
use Ramsey\Identifier\Uuid\Variant;
use Ramsey\Identifier\Uuid\Version;

/**
 * A universally unique identifier (UUID).
 *
 * @link https://www.rfc-editor.org/rfc/rfc9562 RFC 9562: Universally Unique IDentifiers (UUIDs).
 */
interface Uuid extends BytesIdentifier, IntegerIdentifier
{
    /**
     * Returns the variant of this UUID, describing the layout of the UUID.
     *
     * @link https://www.rfc-editor.org/rfc/rfc9562#section-4.1 RFC 9562, section 4.1. Variant Field.
     */
    public function getVariant(): Variant;

    /**
     * Returns the version of this UUID, describing how the UUID was generated.
     *
     * @link https://www.rfc-editor.org/rfc/rfc9562#section-4.2 RFC 9562, section 4.2. Version Field.
     */
    public function getVersion(): Version;

    /**
     * Returns a string representation of the UUID encoded as hexadecimal digits.
     *
     * @return non-empty-string
     */
    public function toHexadecimal(): string;

    /**
     * Returns the standard string representation of the UUID as a URN.
     *
     * @link https://www.rfc-editor.org/rfc/rfc9562#figure-4 RFC 9562, Figure 4: Example URN Namespace for UUID.
     * @link https://www.rfc-editor.org/rfc/rfc8141 RFC 8141: Uniform Resource Names (URNs).
     *
     * @return non-empty-string
     */
    public function toUrn(): string;
}
