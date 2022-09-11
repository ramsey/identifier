<?php

/**
 * This file is part of ramsey/identifier
 *
 * ramsey/identifier is open source software: you can distribute
 * it and/or modify it under the terms of the MIT License
 * (the "License"). You may not use this file except in
 * compliance with the License.
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or
 * implied. See the License for the specific language governing
 * permissions and limitations under the License.
 *
 * @copyright Copyright (c) Ben Ramsey <ben@benramsey.com>
 * @license https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Ramsey\Identifier\Uuid;

use Brick\Math\BigInteger;
use Brick\Math\RoundingMode;
use DateTimeImmutable;
use Exception;

use function explode;
use function str_pad;

use const STR_PAD_LEFT;

/**
 * @psalm-immutable
 */
trait TimeBasedUuid
{
    use StandardUuid;

    /**
     * The number of 100-nanosecond intervals from the Gregorian calendar epoch
     * to the Unix epoch.
     */
    private string $gregorianToUnixIntervals = '122192928000000000';

    /**
     * The number of 100-nanosecond intervals in one second.
     */
    private string $secondIntervals = '10000000';

    /**
     * Returns the full 60-bit timestamp as a hexadecimal string, without the version
     */
    abstract protected function getTimestamp(): string;

    /**
     * @throws Exception When unable to create a DateTimeImmutable instance.
     */
    public function getDateTime(): DateTimeImmutable
    {
        $epochNanoseconds = BigInteger::fromBase($this->getTimestamp(), 16)->minus($this->gregorianToUnixIntervals);
        $unixTimestamp = $epochNanoseconds->dividedBy($this->secondIntervals, RoundingMode::HALF_UP);
        $split = explode('.', (string) $unixTimestamp, 2);

        return new DateTimeImmutable(
            '@'
            . $split[0]
            . '.'
            . str_pad($split[1] ?? '0', 6, '0', STR_PAD_LEFT),
        );
    }
}
