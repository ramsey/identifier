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

namespace Ramsey\Identifier\Service\Sequence;

use OverflowException;
use Ramsey\Identifier\Exception\IdentifierException;

/**
 * Thrown when a sequence reaches its maximum value (i.e., the sequence cannot be incremented beyond its current value).
 */
class SequenceOverflow extends OverflowException implements IdentifierException
{
}
