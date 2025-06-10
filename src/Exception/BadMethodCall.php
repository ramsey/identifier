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

use BadMethodCallException;

/**
 * Thrown when attempting to call a method from an unsupported context (e.g., calling `getVersion()` on a
 * {@see \Ramsey\Identifier\Uuid\MaxUuid}).
 */
class BadMethodCall extends BadMethodCallException implements IdentifierException
{
}
