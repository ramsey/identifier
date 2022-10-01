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

namespace Ramsey\Identifier\Uuid\Utility;

/**
 * @internal
 */
final class Format
{
    /**
     * Bytes representation
     */
    public const FORMAT_BYTES = 16;

    /**
     * Hexadecimal representation
     */
    public const FORMAT_HEX = 32;

    /**
     * String standard representation
     */
    public const FORMAT_STRING = 36;

    /**
     * ULID string representation
     */
    public const FORMAT_ULID = 26;

    /**
     * A mask used with functions like {@see strspn()} to validate Crockford base 32 strings
     */
    public const MASK_CROCKFORD32 = '0123456789abcdefghjkmnpqrstvwxyzABCDEFGHJKMNPQRSTVWXYZ';

    /**
     * A mask used with functions like {@see strspn()} to validate hexadecimal strings
     */
    public const MASK_HEX = '0123456789abcdefABCDEF';

    /**
     * A mask used with functions like {@see strspn()} to validate string integers
     */
    public const MASK_INT = '0123456789';

    /**
     * A mask used with functions like {@see strspn()} to validate Max UUID strings
     */
    public const MASK_MAX = 'fF';

    /**
     * The Crockford base 32 alphabet, for use with ULID base conversion
     */
    public const CROCKFORD32_ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    /**
     * Disallow public instantiation
     */
    private function __construct()
    {
    }
}
