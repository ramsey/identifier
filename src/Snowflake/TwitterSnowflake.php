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
use Ramsey\Identifier\Snowflake\Internal\Standard;

/**
 * A Snowflake identifier for use with the Twitter social media platform.
 *
 * @link http://twitter.com Twitter.
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
        $this->snowflake = new GenericSnowflake($snowflake, Epoch::Twitter);
    }
}
