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

use Ramsey\Identifier\Exception\BadMethodCall;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Uuid;
use Ramsey\Identifier\Uuid\Utility\Format;
use Ramsey\Identifier\Uuid\Utility\Standard;

use function sprintf;
use function strlen;

/**
 * The Max UUID is a special form of UUID that is specified to have all 128
 * bits set to one (1)
 *
 * @link https://www.rfc-editor.org/rfc/rfc9562#section-5.10 RFC 9562, section 5.10: Max UUID
 */
final readonly class MaxUuid implements Uuid
{
    use Standard;

    private const MAX = "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff";

    /**
     * @throws InvalidArgument
     */
    public function __construct(private string $uuid = self::MAX)
    {
        $this->format = Format::tryFrom(strlen($this->uuid));

        if (!$this->isValid($this->uuid, $this->format)) {
            throw new InvalidArgument(sprintf('Invalid Max UUID: "%s"', $this->uuid));
        }
    }

    public function getVariant(): Variant
    {
        // Max UUIDs are defined according to the rules of RFC 9562, so they are
        // an RFC 9562 variant of UUID.
        return Variant::Rfc9562;
    }

    /**
     * @throws BadMethodCall
     */
    public function getVersion(): never
    {
        throw new BadMethodCall('Max UUIDs do not have a version field');
    }

    private function isValid(string $uuid, ?Format $format): bool
    {
        return $this->isMax($uuid, $format);
    }
}
