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

namespace Ramsey\Identifier\Service\RandomGenerator;

/**
 * Defines a random generator interface for generating pseudorandom bytes
 */
interface RandomGenerator
{
    /**
     * Generates an n-length string of random bytes
     *
     * @param int $length The number of bytes to generate
     *
     * @return non-empty-string
     *
     * @psalm-param positive-int $length
     */
    public function bytes(int $length): string;
}
