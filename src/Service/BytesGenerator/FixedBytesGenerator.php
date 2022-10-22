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

use function strlen;
use function substr;

/**
 * A bytes generator that returns a pre-determined string of bytes
 */
final class FixedBytesGenerator implements BytesGenerator
{
    /**
     * @param non-empty-string $bytes
     */
    public function __construct(private readonly string $bytes)
    {
    }

    public function bytes(int $length, ?DateTimeInterface $dateTime = null): string
    {
        if (strlen($this->bytes) > $length) {
            /** @var non-empty-string */
            return substr($this->bytes, 0, $length);
        }

        return $this->bytes;
    }
}
