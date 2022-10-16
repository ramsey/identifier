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

use Ramsey\Identifier\Exception\RandomSourceNotFound;
use Throwable;

use function bin2hex;
use function pack;
use function random_bytes;
use function unpack;

/**
 * A node service that generates a random node and sets the multicast bit
 */
final class RandomNodeService implements NodeService
{
    /**
     * @throws RandomSourceNotFound
     */
    public function getNode(): string
    {
        try {
            $nodeBytes = random_bytes(6);
        // @codeCoverageIgnoreStart
        } catch (Throwable $exception) {
            throw new RandomSourceNotFound('Cannot find an appropriate source of randomness', 0, $exception);
        // @codeCoverageIgnoreEnd
        }

        /** @var int[] $parts */
        $parts = unpack('n*', $nodeBytes);

        /** @var non-empty-string */
        return bin2hex(pack('n*', $parts[1] | 0x0100, $parts[2], $parts[3]));
    }
}
