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

namespace Ramsey\Identifier\Service\BytesGenerator;

use DateTimeInterface;

/**
 * Generates bytes used to create identifiers
 */
interface BytesGenerator
{
    /**
     * Generates an n-length string of bytes.
     *
     * @param positive-int $length The number of bytes to generate.
     * @param DateTimeInterface | null $dateTime An optional date-time instance to use when generating the bytes; not
     *     all generators will need or use this parameter.
     *
     * @return non-empty-string
     */
    public function bytes(int $length = 16, ?DateTimeInterface $dateTime = null): string;
}
