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

use Ramsey\Identifier\Exception\RandomSourceNotFound;
use Throwable;

use function random_int;

use const PHP_INT_MAX;

/**
 * A counter that uses PHP's `random_int()` function to generate the "next"
 * value randomly
 *
 * @link https://www.php.net/random_int random_int()
 */
final class RandomCounter implements Counter
{
    /**
     * @throws RandomSourceNotFound
     */
    public function next(): int
    {
        try {
            return random_int(0, PHP_INT_MAX);
        // @codeCoverageIgnoreStart
        } catch (Throwable $exception) {
            throw new RandomSourceNotFound('Cannot find an appropriate source of randomness', 0, $exception);
        // @codeCoverageIgnoreEnd
        }
    }
}
