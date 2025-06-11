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
