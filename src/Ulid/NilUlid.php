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

use JsonSerializable;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Ulid;
use Ramsey\Identifier\Ulid\Utility\Standard;

use function sprintf;
use function strlen;

/**
 * @psalm-immutable
 */
final class NilUlid implements JsonSerializable, Ulid
{
    use Standard;

    private const NIL = "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00";

    /**
     * @throws InvalidArgument
     */
    public function __construct(private readonly string $ulid = self::NIL)
    {
        $this->format = strlen($this->ulid);

        if (!$this->isValid($this->ulid, $this->format)) {
            throw new InvalidArgument(sprintf('Invalid Nil ULID: "%s"', $this->ulid));
        }
    }

    private function isValid(string $ulid, int $format): bool
    {
        return $this->isNil($ulid, $format);
    }
}
