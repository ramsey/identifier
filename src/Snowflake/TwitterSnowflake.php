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

use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Snowflake;
use Ramsey\Identifier\Snowflake\Internal\Mask;
use Ramsey\Identifier\Snowflake\Internal\Standard;

use function sprintf;
use function strlen;
use function strspn;

/**
 * A Snowflake identifier for use with the X (formerly Twitter) social media platform.
 *
 * @link https://x.com X/Twitter.
 * @link https://blog.twitter.com/engineering/en_us/a/2010/announcing-snowflake Announcing Snowflake.
 * @link https://github.com/twitter-archive/snowflake/tree/snowflake-2010 Snowflake.
 */
final readonly class TwitterSnowflake implements Snowflake
{
    use Standard;

    /**
     * @param int<0, max> | numeric-string $snowflake A representation of the Snowflake in integer or numeric string form.
     *
     * @throws InvalidArgument
     */
    public function __construct(int | string $snowflake)
    {
        if (
            (string) $snowflake > (string) 0x7fffffffffffffff
            && strspn((string) $snowflake, Mask::INT) === strlen((string) $snowflake)
        ) {
            throw new InvalidArgument(sprintf(
                'Twitter Snowflakes are limited to a 41-bit timestamp; the timestamp in "%s" is greater than 41-bits',
                $snowflake,
            ));
        }

        $this->snowflake = new GenericSnowflake($snowflake, Epoch::Twitter);
    }
}
