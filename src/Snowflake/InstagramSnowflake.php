<?php

/**
 * This file is part of ramsey/identifier
 *
 * ramsey/identifier is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser
 * General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.
 *
 * ramsey/identifier is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the
 * implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License
 * for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License along with ramsey/identifier. If not, see
 * <https://www.gnu.org/licenses/>.
 *
 * @copyright Copyright (c) Ben Ramsey <ben@ramsey.dev> and Contributors
 * @license https://opensource.org/license/lgpl-3-0/ GNU Lesser General Public License version 3 or later
 */

declare(strict_types=1);

namespace Ramsey\Identifier\Snowflake;

use DateTimeImmutable;
use Identifier\Exception\OutOfRange;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Snowflake;
use Ramsey\Identifier\Snowflake\Internal\Standard;
use Ramsey\Identifier\Snowflake\Internal\Time;

/**
 * A Snowflake identifier for use with the Instagram photo and video sharing social media platform.
 *
 * @link https://www.instagram.com Instagram.
 * @link https://instagram-engineering.com/sharding-ids-at-instagram-1cf5a71e5a5c Sharding & IDs at Instagram.
 */
final readonly class InstagramSnowflake implements Snowflake
{
    use Standard;

    private const TIMESTAMP_BIT_SHIFTS = 23;

    private Time $time;

    /**
     * @param int<0, max> | numeric-string $snowflake A representation of the Snowflake in integer or numeric string form.
     *
     * @throws InvalidArgument
     */
    public function __construct(int | string $snowflake)
    {
        $this->snowflake = new GenericSnowflake($snowflake, Epoch::Instagram);
        $this->time = new Time();
    }

    /**
     * @throws OutOfRange
     */
    public function getDateTime(): DateTimeImmutable
    {
        return $this->time->getDateTimeForSnowflake($this, Epoch::Instagram->value, self::TIMESTAMP_BIT_SHIFTS);
    }
}
