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

namespace Ramsey\Identifier\Snowflake;

/**
 * Well-known Snowflake epochs
 */
enum Epoch: string
{
    /**
     * The Discord epoch begins at 2015-01-01 00:00:00.000 +00:00
     *
     * @link https://discord.com/developers/docs/reference#snowflakes
     */
    case Discord = '1420070400000';

    /**
     * The Instagram epoch begins at 2011-08-24 21:07:01.721 +00:00
     *
     * @link https://instagram-engineering.com/sharding-ids-at-instagram-1cf5a71e5a5c
     */
    case Instagram = '1314220021721';

    /**
     * The Twitter epoch begins at 2010-11-04 01:42:54.657 +00:00
     *
     * @link https://github.com/twitter-archive/snowflake/blob/snowflake-2010/src/main/scala/com/twitter/service/snowflake/IdWorker.scala#L25
     */
    case Twitter = '1288834974657';
}
