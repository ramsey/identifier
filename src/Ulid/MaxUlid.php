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

namespace Ramsey\Identifier\Ulid;

use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Ulid as UlidInterface;
use Ramsey\Identifier\Ulid\Utility\Format;
use Ramsey\Identifier\Ulid\Utility\Standard;

use function sprintf;
use function strlen;

final readonly class MaxUlid implements UlidInterface
{
    use Standard;

    private const MAX = "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff";

    /**
     * @throws InvalidArgument
     */
    public function __construct(private string $ulid = self::MAX)
    {
        $this->format = Format::tryFrom(strlen($ulid));

        if (!$this->isValid($this->ulid, $this->format)) {
            throw new InvalidArgument(sprintf('Invalid Max ULID: "%s"', $this->ulid));
        }
    }

    private function isValid(string $ulid, ?Format $format): bool
    {
        return $this->isMax($ulid, $format);
    }
}
