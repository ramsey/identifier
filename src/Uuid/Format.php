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

namespace Ramsey\Identifier\Uuid;

/**
 * This internal class identifies a specific format of UUID
 *
 * @internal
 */
final class Format
{
    /**
     * String standard representation
     */
    public const STRING = 36;

    /**
     * Hexadecimal representation
     */
    public const HEX = 32;

    /**
     * Bytes representation
     */
    public const BYTES = 16;

    /**
     * Disallow public instantiation
     */
    private function __construct()
    {
    }
}
