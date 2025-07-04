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
 * A Snowflake identifier for use with the Mastodon open source platform for decentralized social networking.
 *
 * Mastodon Snowflakes created within the same millisecond are not monotonically increasing:
 *
 * > The IDs generated by this scheme are guaranteed to be unique on a given database (as you'd expect), and are also
 * > well-ordered to within 1ms.
 *
 * This was considered acceptable, since the motivation and goal of the feature was "to hide the total number of entries
 * in each table."
 *
 * There are several points to keep in mind if using this Snowflake generation method:
 *
 * - Mastodon generates its Snowflake identifiers within the database, not from within application code; this is "to
 *   avoid depending on the time synchronization of (possibly multiple) Rails app servers."
 *
 * - Mastodon Snowflakes are generated by combining the milliseconds since the Unix Epoch with the first two bytes from
 *   a hash. The hash is created by concatenating the database table name, 16 random bytes, and the millisecond
 *   timestamp (shifted to the left 16 bits). This should make the identifiers unique within a single database, but
 *   uniqueness cannot be guaranteed when generated across multiple machines, especially if the clocks are not
 *   synchronized.
 *
 * phpcs:disable SlevomatCodingStandard.Commenting.DocCommentSpacing
 * @link https://joinmastodon.org Mastodon.
 * @link https://github.com/mastodon/mastodon/blob/ed4788a342a7215ac87b8d34761f0a996c83e699/lib/mastodon/snowflake.rb Mastodon snowflake.rb
 * @link https://github.com/mastodon/mastodon/pull/4801 PR #4801: Non-Serial Snowflake IDs.
 * phpcs:enable SlevomatCodingStandard.Commenting.DocCommentSpacing
 */
final readonly class MastodonSnowflake implements Snowflake
{
    use Standard;

    private const TIMESTAMP_BIT_SHIFTS = 16;

    private Time $time;

    /**
     * @param int<0, max> | numeric-string $snowflake A representation of the Snowflake in integer or numeric string form.
     *
     * @throws InvalidArgument
     */
    public function __construct(int | string $snowflake)
    {
        $this->snowflake = new GenericSnowflake($snowflake, Epoch::Mastodon);
        $this->time = new Time();
    }

    /**
     * @throws OutOfRange
     */
    public function getDateTime(): DateTimeImmutable
    {
        return $this->time->getDateTimeForSnowflake($this, Epoch::Mastodon->value, self::TIMESTAMP_BIT_SHIFTS);
    }
}
