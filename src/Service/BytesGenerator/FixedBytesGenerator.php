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

use function intdiv;
use function str_repeat;
use function strlen;
use function substr;

/**
 * A generator that returns a pre-determined string of bytes.
 */
final readonly class FixedBytesGenerator implements BytesGenerator
{
    private int $bytesLength;

    /**
     * @param non-empty-string $bytes
     */
    public function __construct(private string $bytes)
    {
        $this->bytesLength = strlen($this->bytes);
    }

    public function bytes(int $length = 16, ?DateTimeInterface $dateTime = null): string
    {
        $bytes = str_repeat($this->bytes, intdiv($length, $this->bytesLength) + 1);

        /** @var non-empty-string */
        return substr($bytes, 0, $length);
    }
}
