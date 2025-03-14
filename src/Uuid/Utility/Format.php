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
enum Format: int
{
    /**
     * Bytes representation
     */
    case Bytes = 16;

    /**
     * Hexadecimal representation
     */
    case Hex = 32;

    /**
     * String standard representation
     */
    case String = 36;
}
