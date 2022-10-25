<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier;

use Identifier\BinaryIdentifier;
use Ramsey\Identifier\Exception\BadMethodCall;

class MockBinaryIdentifier implements BinaryIdentifier
{
    public function __construct(private readonly string $bytes)
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
}
