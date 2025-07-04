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

use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Uuid\Internal\Mask;

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
 * A NIC that provides a pre-determined MAC address and sets the multicast bit, according to RFC 9562, section 6.10.
 *
 * @link https://www.rfc-editor.org/rfc/rfc9562#section-6.10 RFC 9562, section 6.10. UUIDs That Do Not Identify the Host.
 */
final readonly class StaticNic implements Nic
{
    private const ADDRESS_ERROR_MESSAGE = 'The NIC address must be a positive 48-bit integer or hexadecimal string';

    /**
     * @var non-empty-string
     */
    private string $address;

    /**
     * @param int<0, 281474976710655> | non-empty-string $address A 48-bit integer or hexadecimal string.
     *
     * @throws InvalidArgument
     */
    public function __construct(int | string $address)
    {
        if (is_int($address)) {
            $this->address = $this->parseIntegerAddress($address);
        } else {
            $this->address = $this->parseHexadecimalAddress($address);
        }
    }

    public function address(): string
    {
        return $this->address;
    }

    /**
     * @param int<0, 281474976710655> $address
     *
     * @return non-empty-string
     */
    private function parseIntegerAddress(int $address): string
    {
        if ($address < 0 || $address > 0xffffffffffff) {
            throw new InvalidArgument(self::ADDRESS_ERROR_MESSAGE);
        }

        /** @var non-empty-string */
        return sprintf('%012s', dechex($address | 0x010000000000));
    }

    /**
     * @param non-empty-string $address
     *
     * @return non-empty-string
     *
     * @throws InvalidArgument
     */
    private function parseHexadecimalAddress(string $address): string
    {
        $length = strlen($address);
        if ($length === 0 || strspn($address, Mask::HEX) !== $length || $length > 12) {
            throw new InvalidArgument(self::ADDRESS_ERROR_MESSAGE);
        }

        /** @var int[] $parts */
        $parts = unpack('n3', (string) hex2bin(sprintf('%012s', $address)));

        /** @var non-empty-string */
        return bin2hex(pack('n3', $parts[1] | 0x0100, $parts[2], $parts[3]));
    }
}
