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
use Brick\Math\RoundingMode;
use DateTimeImmutable;

use function explode;
use function str_pad;

use const STR_PAD_LEFT;

/**
 * This internal trait provides functionality common to time-based UUIDs
 *
 * @internal
 *
 * @psalm-immutable
 */
trait TimeBasedUuid
{
    use StandardUuid;

    /**
     * Returns the full 60-bit timestamp as a hexadecimal string, without the version
     */
    abstract protected function getTimestamp(): string;

    public function getDateTime(): DateTimeImmutable
    {
        $epochNanoseconds = BigInteger::fromBase($this->getTimestamp(), 16)
            ->minus(Time::GREGORIAN_OFFSET_INT);

        $unixTimestamp = $epochNanoseconds->dividedBy(
            Time::NANOSECOND_INTERVALS,
            RoundingMode::HALF_UP,
        );

        $split = explode('.', (string) $unixTimestamp, 2);

        return new DateTimeImmutable(
            '@'
            . $split[0]
            . '.'
            . str_pad($split[1] ?? '0', 6, '0', STR_PAD_LEFT),
        );
    }
}
