<?php

/**
 * This file is part of ramsey/identifier
 *
 * ramsey/identifier is open source software: you can distribute
 * it and/or modify it under the terms of the MIT License
 * (the "License"). You may not use this file except in
 * compliance with the License.
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or
 * implied. See the License for the specific language governing
 * permissions and limitations under the License.
 *
 * @copyright Copyright (c) Ben Ramsey <ben@benramsey.com>
 * @license https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Ramsey\Identifier\Uuid;

use Brick\Math\BigInteger;
use Identifier\Uuid\Variant;
use InvalidArgumentException;
use Ramsey\Identifier\Exception\NotComparableException;
use Stringable;

use function assert;
use function gettype;
use function hex2bin;
use function is_scalar;
use function is_string;
use function preg_match;
use function sprintf;
use function str_replace;
use function strcasecmp;

/**
 * @psalm-immutable
 */
trait StandardUuid
{
    /**
     * String standard representation of the UUID.
     *
     * @var non-empty-string
     */
    private readonly string $uuid;

    /**
     * Returns a PCRE pattern to validate the string standard
     * representation of this UUID
     *
     * To see how this is used, see {@see self::__construct()} and
     * {@see self::__unserialize()}.
     */
    abstract protected function getValidationPattern(): string;

    /**
     * Constructs an {@see \Identifier\UuidInterface} instance from the string
     * standard representation of a UUID
     *
     * @param string $uuid The string standard representation of the UUID
     */
    public function __construct(string $uuid)
    {
        if ($uuid === '' || !preg_match($this->getValidationPattern(), $uuid)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid version %d UUID: "%s"',
                $this->getVersion()->value,
                $uuid,
            ));
        }

        $this->uuid = $uuid;
    }

    /**
     * @inheritDoc
     */
    public function __serialize(): array
    {
        return ['uuid' => $this->uuid];
    }

    public function __toString(): string
    {
        return $this->uuid;
    }

    /**
     * @inheritDoc
     */
    public function __unserialize(array $data): void
    {
        assert(isset($data['uuid']), "'uuid' is not set in serialized data");
        assert(is_string($data['uuid']), "'uuid' in serialized data is not a string");

        /** @psalm-suppress UnusedMethodCall */
        $this->__construct($data['uuid']);
    }

    /**
     * @psalm-return -1 | 0 | 1
     */
    public function compareTo(mixed $other): int
    {
        if ($other === null || is_scalar($other) || $other instanceof Stringable) {
            $compare = strcasecmp($this->toString(), (string) $other);

            return match (true) {
                $compare < 0 => - 1,
                $compare > 0 => 1,
                default => 0,
            };
        }

        throw new NotComparableException(sprintf(
            'Comparison with values of type "%s" is not supported',
            gettype($other),
        ));
    }

    public function equals(mixed $other): bool
    {
        try {
            return $this->compareTo($other) === 0;
        } catch (NotComparableException) {
            return false;
        }
    }

    public function getVariant(): Variant
    {
        return Variant::Rfc4122;
    }

    public function jsonSerialize(): string
    {
        return $this->uuid;
    }

    public function toString(): string
    {
        return $this->uuid;
    }

    public function toBytes(): string
    {
        /** @var non-empty-string */
        return hex2bin($this->toHexadecimal());
    }

    /**
     * @return non-empty-string
     */
    public function toHexadecimal(): string
    {
        /** @var non-empty-string */
        return str_replace('-', '', $this->uuid);
    }

    /**
     * @return int | numeric-string
     */
    public function toInteger(): int | string
    {
        /** @psalm-var numeric-string */
        return BigInteger::fromBase($this->toHexadecimal(), 16)->__toString();
    }

    /**
     * @return non-empty-string
     */
    public function toRfc4122(): string
    {
        return $this->uuid;
    }

    /**
     * @return non-empty-string
     */
    public function toUrn(): string
    {
        return 'urn:uuid:' . $this->uuid;
    }
}
