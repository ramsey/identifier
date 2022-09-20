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

use function hexdec;
use function sprintf;
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
        return $this->determineVariant(
            (int) hexdec(
                substr($this->getFormat(Format::String, $this->uuid), 19, 1),
            ),
        );
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

        if ($this->getVariantFromUuid($uuid) !== Variant::Rfc4122) {
            return true;
        }

        $version = $this->getVersionFromUuid($uuid);

        return $version < 1 || $version > 8;
    }
}
