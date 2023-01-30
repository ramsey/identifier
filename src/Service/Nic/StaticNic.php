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
use Ramsey\Identifier\Uuid\Utility\Format;

use function bin2hex;
use function dechex;
use function hex2bin;
use function is_int;
use function pack;
use function sprintf;
use function strlen;
use function strspn;
use function unpack;

/**
 * A NIC that provides a pre-determined MAC address and sets the multicast bit,
 * according to RFC 4122, section 4.5
 *
 * @link https://www.rfc-editor.org/rfc/rfc4122.html#section-4.5 RFC 4122: Node IDs that Do Not Identify the Host
 * @link https://www.ietf.org/archive/id/draft-ietf-uuidrev-rfc4122bis-00.html#section-6.9 rfc4122bis: UUIDs that Do Not Identify the Host
 */
final readonly class StaticNic implements Nic
{
    /**
     * @var non-empty-string
     */
    private string $address;

    /**
     * @param int<0, max> | string $address A 48-bit integer or hexadecimal string
     *
     * @throws InvalidArgument
     *
     * @psalm-param int<0, max> | non-empty-string $address
     */
    public function __construct(int | string $address)
    {
        if (is_int($address)) {
            $address = $this->parseIntegerAddress($address);
        } else {
            $address = $this->parseHexadecimalAddress($address);
        }

        $this->address = $address;
    }

    public function address(): string
    {
        return $this->address;
    }

    /**
     * @return non-empty-string
     */
    private function parseIntegerAddress(int $address): string
    {
        /** @var non-empty-string */
        return sprintf('%012s', dechex($address | 0x010000000000));
    }

    /**
     * @return non-empty-string
     *
     * @throws InvalidArgument
     */
    private function parseHexadecimalAddress(string $address): string
    {
        if (strspn($address, Format::MASK_HEX) !== strlen($address) || strlen($address) > 12) {
            throw new InvalidArgument('Address must be a 48-bit integer or hexadecimal string');
        }

        /** @var int[] $parts */
        $parts = unpack('n3', (string) hex2bin(sprintf('%012s', $address)));

        /** @var non-empty-string */
        return bin2hex(pack('n3', $parts[1] | 0x0100, $parts[2], $parts[3]));
    }
}
