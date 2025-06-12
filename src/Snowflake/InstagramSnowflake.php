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
