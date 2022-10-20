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

namespace Ramsey\Identifier\Service\Nic;

/**
 * Defines a network interface controller (NIC) for obtaining a MAC address
 *
 * @link https://en.wikipedia.org/wiki/MAC_address MAC address
 */
interface Nic
{
    /**
     * Returns a MAC address as a 12-character hexadecimal string
     *
     * This value must not include any separator characters. If not using the
     * MAC address of the host where the identifier is created, the value should
     * set the multicast bit. See RFC 4122, section 4.5 for more details.
     *
     * @link https://datatracker.ietf.org/doc/html/rfc4122#section-4.5 Node IDs that Do Not Identify the Host
     *
     * @return non-empty-string
     */
    public function address(): string;
}
