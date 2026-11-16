<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier;

use Stringable;
use Throwable;

/**
 * A Stringable object that returns the given string or throws the given
 * exception when converted to a string
 */
final readonly class MockStringable implements Stringable
{
    public function __construct(private string | Throwable $value)
    {
    }

    public function __toString(): string
    {
        if ($this->value instanceof Throwable) {
            throw $this->value;
        }

        return $this->value;
    }
}
