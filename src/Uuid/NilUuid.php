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
use Ramsey\Identifier\Exception\InvalidArgumentException;
use Ramsey\Identifier\Uuid;

use function sprintf;
use function strlen;

/**
 * The Nil UUID is a special form of UUID that is specified to have all 128
 * bits set to zero (0)
 *
 * @link https://datatracker.ietf.org/doc/html/rfc4122#section-4.1.7 Nil UUID
 *
 * @psalm-immutable
 */
final class NilUuid implements UuidInterface
{
    use StandardUuid;

    public function __construct(private readonly string $uuid = Uuid::NIL)
    {
        $this->format = strlen($this->uuid);

        if (!$this->isValid($this->uuid, $this->format)) {
            throw new InvalidArgumentException(sprintf('Invalid Nil UUID: "%s"', $this->uuid));
        }
    }

    public function getVariant(): Variant
    {
        // Nil UUIDs are defined according to the rules of RFC 4122, so they are
        // an RFC 4122 variant of UUID.
        return Variant::Rfc4122;
    }

    public function getVersion(): never
    {
        throw new BadMethodCallException('Nil UUIDs do not have a version field');
    }

    private function isValid(string $uuid, int $format): bool
    {
        return $this->isNil($uuid, $format);
    }
}
