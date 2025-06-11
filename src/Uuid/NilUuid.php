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
use Ramsey\Identifier\Uuid\Utility\Format;
use Ramsey\Identifier\Uuid\Utility\Standard;

use function sprintf;
use function strlen;

/**
 * The Nil UUID is a special form of UUID that has all 128 bits set to zero (0).
 *
 * @link https://www.rfc-editor.org/rfc/rfc9562#section-5.9 RFC 9562, section 5.9. Nil UUID.
 */
final readonly class NilUuid implements Uuid
{
    use Standard;

    private const NIL = "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00";

    /**
     * @throws InvalidArgument
     */
    public function __construct(private string $uuid = self::NIL)
    {
        $this->format = Format::tryFrom(strlen($this->uuid));

        if (!$this->isValid($this->uuid, $this->format)) {
            throw new InvalidArgument(sprintf('Invalid Nil UUID: "%s"', $this->uuid));
        }
    }

    /**
     * {@inheritDoc}
     *
     * According to RFC 9562 sections {@link https://www.rfc-editor.org/rfc/rfc9562#section-4.1 4.1} and
     * {@link https://www.rfc-editor.org/rfc/rfc9562#section-5.9 5.9}, the Nil UUID falls within the range of the Apollo
     * NCS variant.
     */
    public function getVariant(): Variant
    {
        return Variant::Ncs;
    }

    /**
     * @throws BadMethodCall
     */
    public function getVersion(): never
    {
        throw new BadMethodCall('Nil UUIDs do not have a version field');
    }

    private function isValid(string $uuid, ?Format $format): bool
    {
        return $this->isNil($uuid, $format);
    }
}
