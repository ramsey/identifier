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

use function random_int;
use function sprintf;

/**
 * A NIC that generates a random MAC address and sets the multicast bit,
 * according to RFC 4122, section 4.5
 *
 * @link https://datatracker.ietf.org/doc/html/rfc4122#section-4.5 Node IDs that Do Not Identify the Host
 */
final class RandomNic implements Nic
{
    public function address(): string
    {
        /** @var non-empty-string */
        return sprintf('%06x%06x', random_int(0, 0xffffff) | 0x010000, random_int(0, 0xffffff));
    }
}
