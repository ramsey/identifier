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

namespace Ramsey\Identifier\Uuid;

use BadMethodCallException;
use Identifier\Uuid\UuidInterface;
use Identifier\Uuid\Variant;
use InvalidArgumentException;

use function decbin;
use function sprintf;
use function str_pad;
use function substr;
use function unpack;

use const STR_PAD_LEFT;

/**
 * Nonstandard UUIDs look like UUIDs, but they do not have the variant and
 * version bits set according to RFC 4122
 *
 * It is possible a nonstandard UUID was generated according to RFC 4122 but had
 * its bits rearranged for reasons such as sortability. Without knowing which
 * rearrangement algorithm was used, it is impossible to determine to UUID's
 * original layout, so we treat it as a "nonstandard" UUID.
 *
 * @psalm-immutable
 */
final class NonstandardUuid implements UuidInterface
{
    use StandardUuid;

    public function __construct(string $uuid)
    {
        if (!$this->isValid($uuid)) {
            throw new InvalidArgumentException(sprintf('Invalid nonstandard UUID: "%s"', $uuid));
        }

        $this->uuid = $uuid;
    }

    public function getVariant(): Variant
    {
        /** @var int[] $parts */
        $parts = unpack('n*', $this->toBytes());

        // $parts[5] is a 16-bit, unsigned integer containing the variant bits
        // of the UUID. We convert this integer into a string containing a
        // binary representation, padded to 16 characters. We analyze the first
        // three characters (three most-significant bits) to determine the
        // variant.
        $binary = str_pad(decbin($parts[5]), 16, '0', STR_PAD_LEFT);
        $msb = substr($binary, 0, 3);

        return match (true) {
            $msb === '111' => Variant::ReservedFuture,
            $msb === '110' => Variant::ReservedMicrosoft,
            $msb === '100', $msb === '101' => Variant::Rfc4122,
            default => Variant::ReservedNcs,
        };
    }

    public function getVersion(): never
    {
        throw new BadMethodCallException('Nonstandard UUIDs do not have a version field');
    }

    private function isValid(string $uuid): bool
    {
        if (!$this->hasValidFormat($uuid)) {
            return false;
        }

        if ($this->isMax($uuid) || $this->isNil($uuid)) {
            return false;
        }

        if ($this->getVariantFromUuid($uuid) !== 8) {
            return true;
        }

        $version = $this->getVersionFromUuid($uuid);

        return $version < 1 || $version > 8;
    }
}
