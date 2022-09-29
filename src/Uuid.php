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

use Ramsey\Identifier\Uuid\MaxUuid;
use Ramsey\Identifier\Uuid\NilUuid;

/**
 * Uuid provides constants and static methods for working with and generating UUIDs
 */
final class Uuid
{
    /**
     * The Nil UUID is a special form of UUID that is specified to have all 128
     * bits set to zero (0)
     *
     * @link https://datatracker.ietf.org/doc/html/rfc4122#section-4.1.7 RFC 4122, § 4.1.7
     * @see NilUuid
     */
    public const NIL = '00000000-0000-0000-0000-000000000000';

    /**
     * The Max UUID is a special form of UUID that is specified to have all 128
     * bits set to one (1)
     *
     * @link https://datatracker.ietf.org/doc/html/draft-peabody-dispatch-new-uuid-format-04#section-5.4 New UUID Formats, § 5.4
     * @see MaxUuid
     */
    public const MAX = 'ffffffff-ffff-ffff-ffff-ffffffffffff';

    /**
     * Creates a Max UUID with all bits set to one (1)
     */
    public static function max(): MaxUuid
    {
        return self::getFactory()->max();
    }

    /**
     * Creates a Nil UUID with all bits set to zero (0)
     */
    public static function nil(): NilUuid
    {
        return self::getFactory()->nil();
    }

    /**
     * Disallow public instantiation
     */
    private function __construct()
    {
    }
}
