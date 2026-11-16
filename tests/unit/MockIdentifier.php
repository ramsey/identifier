<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier;

use Identifier\Identifier;
use Ramsey\Identifier\Exception\BadMethodCall;

final readonly class MockIdentifier implements Identifier
{
    /**
     * @param non-empty-string $sortKey
     */
    public function __construct(private string $sortKey)
    {
    }

    public function getSortKey(): string
    {
        return $this->sortKey;
    }

    public function compareTo(mixed $other): never
    {
        throw new BadMethodCall();
    }

    public function equals(mixed $other): never
    {
        throw new BadMethodCall();
    }
}
