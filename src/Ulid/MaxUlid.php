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

use Identifier\Ulid\UlidInterface;
use Ramsey\Identifier\Exception\InvalidArgumentException;
use Ramsey\Identifier\Ulid\Utility\StandardUlid;

use function sprintf;
use function strlen;

/**
 * @psalm-immutable
 */
final class MaxUlid implements UlidInterface
{
    use StandardUlid;

    private const MAX = "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff";

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(private readonly string $ulid = self::MAX)
    {
        $this->format = strlen($this->ulid);

        if (!$this->isValid($this->ulid, $this->format)) {
            throw new InvalidArgumentException(sprintf('Invalid Max ULID: "%s"', $this->ulid));
        }
    }

    private function isValid(string $ulid, int $format): bool
    {
        return $this->isMax($ulid, $format);
    }
}
