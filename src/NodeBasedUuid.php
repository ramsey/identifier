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

/**
 * A UUID that includes a node identifier (or MAC address).
 */
interface NodeBasedUuid extends Uuid
{
    /**
     * Returns a string representation of the node (usually the host MAC address), encoded as hexadecimal characters.
     *
     * If the node has the multicast bit set, this indicates it was randomly generated, rather than identifying a host
     * machine.
     *
     * @link https://www.rfc-editor.org/rfc/rfc9562#section-5.1 RFC 9562, section 5.1. UUID Version 1.
     * @link https://www.rfc-editor.org/rfc/rfc9562#section-5.6 RFC 9562, section 5.6. UUID Version 6.
     * @link https://www.rfc-editor.org/rfc/rfc9562#section-6.10 RFC 9562, section 6.10. UUIDs That Do Not Identify the Host.
     *
     * @return non-empty-string
     */
    public function getNode(): string;
}
