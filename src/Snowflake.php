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

namespace Ramsey\Identifier;

use Identifier\BytesIdentifier;
use Identifier\DateTimeIdentifier;
use Identifier\IntegerIdentifier;

/**
 * A Snowflake identifier.
 *
 * @link https://github.com/twitter-archive/snowflake/tree/snowflake-2010 Twitter Snowflakes.
 * @link https://discord.com/developers/docs/reference#snowflakes Discord Snowflakes.
 * @link https://instagram-engineering.com/sharding-ids-at-instagram-1cf5a71e5a5c Instagram Snowflakes.
 * @link https://github.com/mastodon/mastodon/blob/04492e7f934d07f8e89fa9c3d4fe3381f251e8a2/lib/mastodon/snowflake.rb Mastodon Snowflakes.
 */
interface Snowflake extends BytesIdentifier, DateTimeIdentifier, IntegerIdentifier
{
    /**
     * Returns a string representation of the Snowflake encoded as hexadecimal digits.
     */
    public function toHexadecimal(): string;
}
