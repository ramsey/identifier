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

namespace Ramsey\Identifier\Uuid\Utility;

use Brick\Math\BigInteger;
use Brick\Math\Exception\MathException;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Uuid;
use Throwable;

use function sprintf;
use function str_pad;
use function strlen;

use const STR_PAD_LEFT;

/**
 * This internal trait provides common factory functionality for RFC 4122 UUIDs
 *
 * @internal
 */
trait StandardFactory
{
    use Validation;

    /**
     * Returns the name of the UUID class to use when instantiating UUID
     * instances from this trait
     *
     * @return class-string<Uuid>
     */
    abstract protected function getUuidClass(): string;

    /**
     * @throws InvalidArgument
     */
    private function createFromBytesInternal(string $identifier): Uuid
    {
        if (strlen($identifier) === Format::FORMAT_BYTES) {
            /** @var Uuid */
            return new ($this->getUuidClass())($identifier);
        }

        throw new InvalidArgument('Identifier must be a 16-byte string');
    }

    /**
     * The minimum integer value for a version 1 UUID is 75567087097951178194944.
     * As such, there's no need to use better performing math for integers less
     * than PHP_INT_MAX, since those integers can never be valid RFC 4122 UUIDs.
     *
     * @throws InvalidArgument
     */
    private function createFromIntegerInternal(int | string $identifier): Uuid
    {
        try {
            $bigInteger = BigInteger::of($identifier);
        } catch (MathException $exception) {
            throw new InvalidArgument(sprintf('Invalid integer: "%s"', $identifier), 0, $exception);
        }

        try {
            return $this->createFromBytesInternal(str_pad($bigInteger->toBytes(false), 16, "\x00", STR_PAD_LEFT));
        } catch (Throwable $exception) {
            /**
             * @psalm-suppress InvalidPropertyFetch,MixedArgument When called in
             *     this context, getVersion() will never return "never," but
             *     Psalm can't verify this, so it complains.
             */
            throw new InvalidArgument(
                sprintf('Invalid version %d UUID: %s', $this->getVersion()->value, $identifier),
                0,
                $exception,
            );
        }
    }

    /**
     * @throws InvalidArgument
     */
    private function createFromStringInternal(string $identifier): Uuid
    {
        if (
            strlen($identifier) === Format::FORMAT_STRING
            && $this->hasValidFormat($identifier, Format::FORMAT_STRING)
        ) {
            /** @var Uuid */
            return new ($this->getUuidClass())($identifier);
        }

        throw new InvalidArgument('Identifier must be a UUID in string standard representation');
    }
}
