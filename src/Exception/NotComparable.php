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

namespace Ramsey\Identifier\Exception;

use Identifier\Exception\NotComparable as IdentifierNotComparable;
use RuntimeException;

/**
 * Thrown when unable to compare values, e.g. because the other value is not of
 * the proper type, etc.
 */
class NotComparable extends RuntimeException implements
    IdentifierException,
    IdentifierNotComparable
{
}
