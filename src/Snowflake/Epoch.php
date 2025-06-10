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

use DateTimeImmutable;

/**
 * Well-known Snowflake epochs.
 */
enum Epoch: int
{
    /**
     * The Discord epoch begins at 2015-01-01 00:00:00.000 +00:00.
     *
     * @link https://discord.com/developers/docs/reference#snowflakes Discord Snowflakes.
     */
    case Discord = 1_420_070_400_000;

    /**
     * The Instagram epoch begins at 2011-08-24 21:07:01.721 +00:00.
     *
     * The calculation within the Instagram blog post appears incorrect. The blog post states:
     *
     * > Let's walk through an example: let's say it's September 9th, 2011, at 5:00pm and our 'epoch' begins on January
     * > 1st, 2011. There have been 1387263000 milliseconds since the beginning of our epoch…
     *
     * If the current date is September 9th, 2011, at 5:00pm, and there have been 1,387,263,000 milliseconds since the
     * beginning of the epoch, then the epoch must have begun on August 24, 2011. Later in the same post, they use some
     * PL/PGSQL code to illustrate the calculation. In it, the epoch is written as `1314220021721`, which is on August
     * 24, 2011, if this is a count of milliseconds since the Unix epoch. This is the value we use, since it appears to
     * align with all their examples.
     *
     * @link https://instagram-engineering.com/sharding-ids-at-instagram-1cf5a71e5a5c Instagram Snowflakes.
     */
    case Instagram = 1_314_220_021_721;

    /**
     * The Twitter epoch begins at 2010-11-04 01:42:54.657 +00:00.
     *
     * @link https://github.com/twitter-archive/snowflake/blob/snowflake-2010/src/main/scala/com/twitter/service/snowflake/IdWorker.scala#L25 Twitter Snowflakes.
     */
    case Twitter = 1_288_834_974_657;

    /**
     * ISO 8601 extended format (includes millisecond precision).
     */
    public const ISO_EXTENDED_FORMAT = 'Y-m-d\TH:i:s.vp';

    /**
     * Returns the epoch as a date-time string in ISO 8601 extended format.
     */
    public function toIso8601(): string
    {
        return (new DateTimeImmutable('@' . $this->value / 1000))->format(self::ISO_EXTENDED_FORMAT);
    }
}
