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
use DateTimeInterface;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Service\Os\Os;
use Ramsey\Identifier\Service\Os\PhpOs;

use function pack;
use function str_pad;

use const STR_PAD_LEFT;

/**
 * @internal
 */
final class Time
{
    /**
     * The number of 100-nanosecond intervals as bytes between the
     * Gregorian epoch 1582-10-15 00:00:00 and the Unix epoch 1970-01-01 00:00:00.
     */
    public const GREGORIAN_OFFSET_BIN = "\x01\xb2\x1d\xd2\x13\x81\x40\x00";

    /**
     * The number of 100-nanosecond intervals in one second.
     */
    public const NANOSECOND_INTERVALS = 10000000;

    /**
     * The 100-nanosecond interval count of the Gregorian Epoch, relative to
     * the Unix Epoch. This is stored as a string, since we use it only for
     * string comparison.
     *
     * Derived from:
     *
     * ```php
     * (new DateTimeImmutable('1582-10-15 00:00:00.0'))->format('Uu0');
     * ```
     */
    private const GREGORIAN_EPOCH_NANOSECONDS = '-122192928000000000';

    /**
     * The number of 100-nanosecond intervals as a native integer between the
     * Gregorian epoch 1582-10-15 00:00:00 and the Unix epoch 1970-01-01 00:00:00.
     */
    private const GREGORIAN_OFFSET_INT = 0x01b21dd213814000;

    public function __construct(private readonly Os $os = new PhpOs())
    {
    }

    /**
     * Returns an 8-byte string representing a count of 100-nanosecond intervals
     * since the Gregorian epoch, 1582-10-15 00:00:00
     *
     * @param DateTimeInterface $dateTime The date-time for which to construct
     *     a count of 100-nanosecond intervals since the Gregorian epoch
     *
     * @return non-empty-string
     *
     * @throws InvalidArgument
     */
    public function getTimeBytesForGregorianEpoch(DateTimeInterface $dateTime): string
    {
        $ns = $dateTime->format('Uu0');

        if ($ns < self::GREGORIAN_EPOCH_NANOSECONDS) {
            throw new InvalidArgument('Unable to get bytes for a timestamp earlier than the Gregorian epoch');
        }

        if ($this->os->getIntSize() >= 8) {
            /** @var non-empty-string */
            return pack('J', (int) $ns + self::GREGORIAN_OFFSET_INT);
        }

        /** @var non-empty-string */
        return str_pad(
            BigInteger::of($ns)->plus(BigInteger::fromBytes(self::GREGORIAN_OFFSET_BIN))->toBytes(false),
            8,
            "\x00",
            STR_PAD_LEFT,
        );
    }
}
