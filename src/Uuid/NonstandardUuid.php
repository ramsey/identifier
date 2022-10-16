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

use JsonSerializable;
use Ramsey\Identifier\Exception\BadMethodCall;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Uuid\Utility\Format;
use Ramsey\Identifier\Uuid\Utility\StandardUuid;
use Ramsey\Identifier\UuidIdentifier;

use function hexdec;
use function sprintf;
use function strlen;
use function substr;

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
final class NonstandardUuid implements JsonSerializable, UuidIdentifier
{
    use StandardUuid;

    /**
     * @throws InvalidArgument
     */
    public function __construct(private readonly string $uuid)
    {
        $this->format = strlen($this->uuid);

        if (!$this->isValid($this->uuid, $this->format)) {
            throw new InvalidArgument(sprintf('Invalid nonstandard UUID: "%s"', $this->uuid));
        }
    }

    public function getVariant(): Variant
    {
        return $this->determineVariant(
            (int) hexdec(substr($this->getFormat(Format::FORMAT_STRING), 19, 1)),
        );
    }

    /**
     * @throws BadMethodCall
     */
    public function getVersion(): never
    {
        throw new BadMethodCall('Nonstandard UUIDs do not have a version field');
    }

    private function isValid(string $uuid, int $format): bool
    {
        if (!$this->hasValidFormat($uuid, $format)) {
            return false;
        }

        if ($this->isMax($uuid, $format) || $this->isNil($uuid, $format)) {
            return false;
        }

        if ($this->getVariantFromUuid($uuid, $format) !== Variant::Rfc4122) {
            return true;
        }

        $version = $this->getVersionFromUuid($uuid, $format);

        // Version 2 UUIDs that do not have a proper domain are nonstandard.
        if ($version === 2 && $this->getLocalDomainFromUuid($uuid, $format) === null) {
            return true;
        }

        return $version < 1 || $version > 8;
    }
}
