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

use Ramsey\Identifier\Exception\InvalidArgumentException;
use Ramsey\Identifier\Mask;

use function bin2hex;
use function hex2bin;
use function is_int;
use function pack;
use function sprintf;
use function strlen;
use function strspn;
use function substr;
use function unpack;

/**
 * A node service that provides a static node value with the multicast bit set
 */
final class StaticNodeService implements NodeServiceInterface
{
    /**
     * @var non-empty-string
     */
    private readonly string $node;

    /**
     * @param int<0, max> | string $node A 48-bit integer or hexadecimal string
     *
     * @psalm-param int<0, max> | non-empty-string $node
     */
    public function __construct(int | string $node)
    {
        if (is_int($node)) {
            /** @var non-empty-string $node */
            $node = substr(bin2hex(pack('J*', $node | 0x010000000000)), 4);
        } elseif (strspn($node, Mask::Hex->value) === strlen($node) && strlen($node) <= 12) {
            /** @var int[] $parts */
            $parts = unpack('n*', (string) hex2bin(sprintf('%012s', $node)));

            /** @var non-empty-string $node */
            $node = bin2hex(pack('n*', $parts[1] | 0x0100, $parts[2], $parts[3]));
        } else {
            throw new InvalidArgumentException(
                'Node must be a 48-bit integer or hexadecimal string',
            );
        }

        $this->node = $node;
    }

    public function getNode(): string
    {
        return $this->node;
    }
}
