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

namespace Ramsey\Identifier\Service\ClockSequence;

use Exception;

use function random_int;

/**
 * A clock sequence service that uses PHP's built-in `random_int()` function to
 * generate a random clock sequence value
 *
 * @link https://www.php.net/random_int random_int()
 */
final class RandomClockSequenceService implements ClockSequenceServiceInterface
{
    /**
     * @var int<0, 16383> | null
     */
    private static ?int $clockSequence = null;

    /**
     * @throws Exception If a suitable source of randomness is not available
     */
    public function getClockSequence(): int
    {
        $sequence = random_int(0, 0x3fff);

        while ($sequence === self::$clockSequence) {
            // @codeCoverageIgnoreStart
            $sequence = random_int(0, 0x3fff);
            // @codeCoverageIgnoreEnd
        }

        self::$clockSequence = $sequence;

        return $sequence;
    }
}
