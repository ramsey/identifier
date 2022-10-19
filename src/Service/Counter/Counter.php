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

namespace Ramsey\Identifier\Service\Counter;

/**
 * Defines a counter interface for obtaining the next value in a sequence, for
 * the purpose of avoiding duplicates or collisions
 */
interface Counter
{
    /**
     * Returns the next value in the sequence
     *
     * @return int<0, max>
     */
    public function next(): int;
}
