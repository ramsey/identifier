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

namespace Ramsey\Identifier;

use Identifier\BinaryIdentifier;
use Identifier\DateTimeIdentifier;
use Identifier\IntegerIdentifier;

/**
 * Describes the interface of a Snowflake identifier
 *
 * @link https://en.wikipedia.org/wiki/Snowflake_ID Snowflake ID
 * @link https://github.com/twitter-archive/snowflake/tree/snowflake-2010 Twitter Snowflakes
 * @link https://discord.com/developers/docs/reference#snowflakes Discord Snowflakes
 * @link https://instagram-engineering.com/sharding-ids-at-instagram-1cf5a71e5a5c Instagram Snowflakes
 */
interface Snowflake extends BinaryIdentifier, DateTimeIdentifier, IntegerIdentifier
{
    /**
     * Returns a string representation of the Snowflake encoded as hexadecimal digits
     */
    public function toHexadecimal(): string;
}
