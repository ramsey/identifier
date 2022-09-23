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

namespace Ramsey\Identifier\Service\Node;

use Exception;

use function bin2hex;
use function pack;
use function random_bytes;
use function unpack;

/**
 * A node service that generates a random node and sets the multicast bit
 */
final class RandomNodeService
{
    /**
     * @throws Exception If a suitable source of randomness is not available
     */
    public function getNode(): string
    {
        $nodeBytes = random_bytes(6);

        /** @var int[] $parts */
        $parts = unpack('n*', $nodeBytes);

        // Set the multicast bit (the least significant bit of the first octet).
        return bin2hex(pack('n*', $parts[1] | 0x0100, $parts[2], $parts[3]));
    }
}
