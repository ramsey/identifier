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

use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Snowflake;
use Ramsey\Identifier\Snowflake\Utility\Standard;

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
     * Constructs a Snowflake identifier using Twitter's Unix Epoch offset.
     *
     * @param int | numeric-string $snowflake A representation of the Snowflake in integer or numeric string form.
     *
     * @throws InvalidArgument
     */
    public function __construct(int | string $snowflake)
    {
        $this->snowflake = new GenericSnowflake($snowflake, Epoch::Twitter);
    }
}
