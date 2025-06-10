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

use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Snowflake;
use Ramsey\Identifier\Snowflake\Utility\Standard;

final readonly class DiscordSnowflake implements Snowflake
{
    use Standard;

    /**
     * Constructs a Snowflake identifier using Discord's Unix Epoch offset
     *
     * @param int | numeric-string $snowflake A representation of the
     *     Snowflake in integer or numeric string form
     *
     * @throws InvalidArgument
     */
    public function __construct(int | string $snowflake)
    {
        $this->snowflake = new GenericSnowflake($snowflake, Epoch::Discord);
    }
}
