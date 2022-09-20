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
use Ramsey\Identifier\Uuid;

use function sprintf;

/**
 * The Max UUID is a special form of UUID that is specified to have all 128
 * bits set to one (1)
 *
 * @link https://datatracker.ietf.org/doc/html/draft-peabody-dispatch-new-uuid-format-04#section-5.4 Max UUID
 *
 * @psalm-immutable
 */
final class MaxUuid implements UuidInterface
{
    use StandardUuid;

    public function __construct(string $uuid = Uuid::MAX)
    {
        if (!$this->isValid($uuid)) {
            throw new InvalidArgumentException(sprintf('Invalid Max UUID: "%s"', $uuid));
        }

        $this->uuid = $uuid;
    }

    public function getVariant(): Variant
    {
        // Max UUIDs are defined according to the rules of RFC 4122, so they are
        // an RFC 4122 variant of UUID.
        return Variant::Rfc4122;
    }

    public function getVersion(): never
    {
        throw new BadMethodCallException('Max UUIDs do not have a version field');
    }

    private function isValid(string $uuid): bool
    {
        return $this->isMax($uuid);
    }
}
