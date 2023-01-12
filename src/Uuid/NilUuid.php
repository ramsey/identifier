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
use Ramsey\Identifier\Uuid;
use Ramsey\Identifier\Uuid\Utility\Standard;

use function sprintf;
use function strlen;

/**
 * The Nil UUID is a special form of UUID that is specified to have all 128
 * bits set to zero (0)
 *
 * @link https://www.rfc-editor.org/rfc/rfc4122.html#section-4.1.7 RFC 4122: Nil UUID
 * @link https://www.ietf.org/archive/id/draft-ietf-uuidrev-rfc4122bis-00.html#name-nil-uuid rfc4122bis: Nil UUID
 *
 * @psalm-immutable
 */
final class NilUuid implements JsonSerializable, Uuid
{
    use Standard;

    private const NIL = "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00";

    /**
     * @throws InvalidArgument
     */
    public function __construct(private readonly string $uuid = self::NIL)
    {
        $this->format = strlen($this->uuid);

        if (!$this->isValid($this->uuid, $this->format)) {
            throw new InvalidArgument(sprintf('Invalid Nil UUID: "%s"', $this->uuid));
        }
    }

    public function getVariant(): Variant
    {
        // Nil UUIDs are defined according to the rules of RFC 4122, so they are
        // an RFC 4122 variant of UUID.
        return Variant::Rfc4122;
    }

    /**
     * @throws BadMethodCall
     */
    public function getVersion(): never
    {
        throw new BadMethodCall('Nil UUIDs do not have a version field');
    }

    private function isValid(string $uuid, int $format): bool
    {
        return $this->isNil($uuid, $format);
    }
}
