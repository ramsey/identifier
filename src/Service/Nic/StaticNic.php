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

use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Service\Os\Os;
use Ramsey\Identifier\Service\Os\PhpOs;
use Ramsey\Identifier\Uuid\Utility\Format;

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
 * A NIC that provides a pre-determined MAC address and sets the multicast bit,
 * according to RFC 4122, section 4.5
 *
 * @link https://datatracker.ietf.org/doc/html/rfc4122#section-4.5 Node IDs that Do Not Identify the Host
 */
final class StaticNic implements Nic
{
    /**
     * @var non-empty-string
     */
    private readonly string $address;

    /**
     * @param int<0, max> | string $address A 48-bit integer or hexadecimal string
     *
     * @throws InvalidArgument
     *
     * @psalm-param int<0, max> | non-empty-string $address
     */
    public function __construct(int | string $address, private readonly Os $os = new PhpOs())
    {
        if (is_int($address)) {
            if ($this->os->getIntSize() >= 8) {
                /** @var non-empty-string $address */
                $address = substr(bin2hex(pack('J*', $address | 0x010000000000)), -12);
            } else {
                /** @var int[] $parts */
                $parts = unpack('n*', pack('N*', $address));

                /** @var non-empty-string $address */
                $address = bin2hex(pack('n*', 0x0100, ...$parts));
            }
        } elseif (strspn($address, Format::MASK_HEX) === strlen($address) && strlen($address) <= 12) {
            /** @var int[] $parts */
            $parts = unpack('n*', (string) hex2bin(sprintf('%012s', $address)));

            /** @var non-empty-string $address */
            $address = bin2hex(pack('n*', $parts[1] | 0x0100, $parts[2], $parts[3]));
        } else {
            throw new InvalidArgument(
                'Address must be a 48-bit integer or hexadecimal string',
            );
        }

        $this->address = $address;
    }

    public function address(): string
    {
        return $this->address;
    }
}
