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

namespace Ramsey\Identifier\Uuid\Factory;

use Brick\Math\BigInteger;
use Brick\Math\Exception\MathException;
use Identifier\Uuid\UuidInterface;
use Ramsey\Identifier\Exception\InvalidArgumentException;
use Ramsey\Identifier\Uuid\Validation;

use function sprintf;
use function str_pad;
use function strlen;

use const STR_PAD_LEFT;

/**
 * This internal trait provides common factory functionality for RFC 4122 UUIDs
 *
 * @internal
 */
trait DefaultFactory
{
    use Validation;

    /**
     * Returns the name of the UUID class to use when instantiating UUID
     * instances from this trait
     *
     * @return class-string<UuidInterface>
     */
    abstract protected function getUuidClass(): string;

    private function createFromBytesInternal(string $identifier): UuidInterface
    {
        if (strlen($identifier) === 16) {
            /** @var UuidInterface */
            return new ($this->getUuidClass())($identifier);
        }

        throw new InvalidArgumentException('Identifier must be a 16-byte string');
    }

    private function createFromHexadecimalInternal(string $identifier): UuidInterface
    {
        if (strlen($identifier) === 32 && $this->hasValidFormat($identifier)) {
            /** @var UuidInterface */
            return new ($this->getUuidClass())($identifier);
        }

        throw new InvalidArgumentException('Identifier must be a 32-character hexadecimal string');
    }

    private function createFromIntegerInternal(int | string $identifier): UuidInterface
    {
        try {
            $bigInteger = BigInteger::of($identifier);
        } catch (MathException $exception) {
            throw new InvalidArgumentException(sprintf('Invalid integer: "%s"', $identifier), 0, $exception);
        }

        try {
            return $this->createFromBytesInternal(str_pad($bigInteger->toBytes(false), 16, "\x00", STR_PAD_LEFT));
        } catch (InvalidArgumentException) {
            throw new InvalidArgumentException(
                sprintf('Invalid version %d UUID: %s', $this->getVersion()->value, $identifier),
            );
        }
    }

    private function createFromStringInternal(string $identifier): UuidInterface
    {
        if (strlen($identifier) === 36 && $this->hasValidFormat($identifier)) {
            /** @var UuidInterface */
            return new ($this->getUuidClass())($identifier);
        }

        throw new InvalidArgumentException('Identifier must be a UUID in string standard representation');
    }
}
