<?php

/**
 * This file is part of ramsey/identifier
 *
 * ramsey/identifier is open source software: you can distribute it and/or modify it under the terms of the MIT License
 * (the "License"). You may not use this file except in compliance with the License.
 *
 * @copyright Copyright (c) Ben Ramsey <ben@benramsey.com>
 * @license https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Ramsey\Identifier\Uuid;

use Ramsey\Identifier\Exception\BadMethodCall;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Uuid;
use Ramsey\Identifier\Uuid\Internal\Format;
use Ramsey\Identifier\Uuid\Internal\Standard;

use function sprintf;
use function strlen;

/**
 * The Max UUID is a special form of UUID that has all 128 bits set to one (1).
 *
 * @link https://www.rfc-editor.org/rfc/rfc9562#section-5.10 RFC 9562, section 5.10: Max UUID.
 */
final readonly class MaxUuid implements Uuid
{
    use Standard;

    private const MAX = "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff";

    /**
     * @param non-empty-string $uuid A representation of the UUID as a string with dashes, hexadecimal, or byte string.
     *
     * @throws InvalidArgument
     */
    public function __construct(private string $uuid = self::MAX)
    {
        $this->format = Format::tryFrom(strlen($this->uuid));

        if (!$this->isValid($this->uuid, $this->format)) {
            throw new InvalidArgument(sprintf('Invalid Max UUID: "%s"', $this->uuid));
        }
    }

    /**
     * {@inheritDoc}
     *
     * According to RFC 9562 sections {@link https://www.rfc-editor.org/rfc/rfc9562#section-4.1 4.1} and
     * {@link https://www.rfc-editor.org/rfc/rfc9562#section-5.10 5.10}, the Max UUID falls within the range
     * of the future variant.
     */
    public function getVariant(): Variant
    {
        return Variant::Future;
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
