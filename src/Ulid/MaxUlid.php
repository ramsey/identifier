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

use Identifier\BinaryIdentifier;
use Identifier\DateTimeIdentifier;
use Identifier\IntegerIdentifier;
use JsonSerializable;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Ulid\Utility\StandardUlid;

use function sprintf;
use function strlen;

/**
 * @psalm-immutable
 */
final class MaxUlid implements BinaryIdentifier, DateTimeIdentifier, IntegerIdentifier, JsonSerializable
{
    use StandardUlid;

    private const MAX = "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff";

    /**
     * @throws InvalidArgument
     */
    public function __construct(private readonly string $ulid = self::MAX)
    {
        $this->format = strlen($this->ulid);

        if (!$this->isValid($this->ulid, $this->format)) {
            throw new InvalidArgument(sprintf('Invalid Max ULID: "%s"', $this->ulid));
        }
    }

    private function isValid(string $ulid, int $format): bool
    {
        return $this->isMax($ulid, $format);
    }
}
