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

use JsonSerializable;
use Ramsey\Identifier\Snowflake;
use Ramsey\Identifier\Snowflake\Utility\Standard;

/**
 * @psalm-immutable
 */
final class TwitterSnowflake implements JsonSerializable, Snowflake
{
    use Standard;

    /**
     * The Twitter epoch begins at 2010-11-04 01:42:54.657 +00:00. It has enough
     * space to create IDs up until 2080-07-10 17:30:30.208.
     */
    public const TWITTER_EPOCH_OFFSET = '1288834974657';

    /**
     * @return int | numeric-string
     */
    protected function getEpochOffset(): int | string
    {
        return self::TWITTER_EPOCH_OFFSET;
    }
}
