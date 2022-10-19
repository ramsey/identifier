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

use Ramsey\Identifier\Exception\RandomSourceNotFound;
use Throwable;

use function bin2hex;
use function pack;
use function random_bytes;
use function unpack;

/**
 * A NIC that generates a random MAC address and sets the multicast bit,
 * according to RFC 4122, section 4.5
 *
 * @link https://datatracker.ietf.org/doc/html/rfc4122#section-4.5 Node IDs that Do Not Identify the Host
 */
final class RandomNic implements Nic
{
    /**
     * @throws RandomSourceNotFound
     */
    public function address(): string
    {
        try {
            $bytes = random_bytes(6);
        // @codeCoverageIgnoreStart
        } catch (Throwable $exception) {
            throw new RandomSourceNotFound('Unable to find an appropriate source of randomness', 0, $exception);
        // @codeCoverageIgnoreEnd
        }

        /** @var int[] $parts */
        $parts = unpack('n*', $bytes);

        /** @var non-empty-string */
        return bin2hex(pack('n*', $parts[1] | 0x0100, $parts[2], $parts[3]));
    }
}
