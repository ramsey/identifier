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

namespace Ramsey\Identifier\Service\Random;

/**
 * Defines a service interface for getting cryptographically secure random or
 * pseudorandom bytes
 */
interface RandomService
{
    /**
     * Generates an n-length string of cryptographically-secure random bytes
     *
     * @param int $length The number of bytes to generate
     *
     * @return non-empty-string
     *
     * @psalm-param positive-int $length
     */
    public function getRandomBytes(int $length): string;
}
