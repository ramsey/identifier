<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier;

use Identifier\BytesIdentifier;
use Ramsey\Identifier\Exception\BadMethodCall;

readonly class MockBinaryIdentifier implements BytesIdentifier
{
    /**
     * @param non-empty-string $bytes
     */
    public function __construct(private string $bytes)
    {
    }

    public function toBytes(): string
    {
        return $this->bytes;
    }

    public function compareTo(mixed $other): never
    {
        throw new BadMethodCall();
    }

    public function equals(mixed $other): never
    {
        throw new BadMethodCall();
    }

    public function toString(): never
    {
        throw new BadMethodCall();
    }

    public function jsonSerialize(): string
    {
        throw new BadMethodCall();
    }

    public function __toString(): string
    {
        throw new BadMethodCall();
    }
}
