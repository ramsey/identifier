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

namespace Ramsey\Identifier\Uuid\Utility;

use Brick\Math\BigInteger;
use Brick\Math\Exception\MathException;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Uuid;
use Throwable;

use function sprintf;
use function str_pad;

use const STR_PAD_LEFT;

/**
 * Provides common methods for UUID factories.
 *
 * @internal Not intended for use outside ramsey/identifier; may change without notice.
 */
trait StandardFactory
{
    use Validation;

    /**
     * Returns the name of the UUID class to use when instantiating UUID instances from this trait.
     *
     * @return class-string<Uuid>
     */
    abstract protected function getUuidClass(): string;

    /**
     * @throws InvalidArgument
     */
    private function createFromBytesInternal(string $identifier): Uuid
    {
        if ($this->hasValidFormat($identifier, Format::Bytes)) {
            /** @var Uuid */
            return new ($this->getUuidClass())($identifier);
        }

        throw new InvalidArgument('Identifier must be a 16-byte string');
    }

    /**
     * @throws InvalidArgument
     */
    private function createFromHexadecimalInternal(string $identifier): Uuid
    {
        if ($this->hasValidFormat($identifier, Format::Hex)) {
            /** @var Uuid */
            return new ($this->getUuidClass())($identifier);
        }

        throw new InvalidArgument('Identifier must be a 32-character hexadecimal string');
    }

    /**
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
        if ($this->hasValidFormat($identifier, Format::String)) {
            /** @var Uuid */
            return new ($this->getUuidClass())($identifier);
        }

        throw new InvalidArgument('Identifier must be a UUID in string standard representation');
    }
}
