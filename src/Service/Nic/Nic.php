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

namespace Ramsey\Identifier\Service\Nic;

/**
 * Derives a MAC address from a network interface controller (NIC).
 *
 * @link https://en.wikipedia.org/wiki/MAC_address MAC address.
 */
interface Nic
{
    /**
     * Returns a MAC address as a 12-character hexadecimal string.
     *
     * This value must not include any separator characters. If not using the MAC address of the host where the
     * identifier is created, the value should set the multicast bit. See RFC 9562, section 6.10, for more details.
     *
     * @link https://www.rfc-editor.org/rfc/rfc9562#section-6.10 RFC 9562, section 6.10. UUIDs That Do Not Identify the Host.
     *
     * @return non-empty-string A 12-character hexadecimal string.
     */
    public function address(): string;
}
