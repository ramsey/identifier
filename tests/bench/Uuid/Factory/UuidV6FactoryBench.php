<?php

declare(strict_types=1);

namespace Ramsey\Bench\Identifier\Uuid\Factory;

use Ramsey\Identifier\Uuid\Factory\UuidV6Factory;

final class UuidV6FactoryBench
{
    private UuidV6Factory $factory;

    public function __construct()
    {
        $this->factory = new UuidV6Factory();
    }

    public function benchCreate(): void
    {
        $this->factory->create();
    }

    public function benchCreateFromBytes(): void
    {
        $this->factory->createFromBytes("\xff\xff\xff\xff\xff\xff\x6f\xff\x8f\xff\xff\xff\xff\xff\xff\xff");
    }

    public function benchCreateFromHexadecimal(): void
    {
        $this->factory->createFromHexadecimal('ffffffffffff6fff8fffffffffffffff');
    }

    public function benchCreateFromInteger(): void
    {
        $this->factory->createFromInteger('340282366920937783437989388184430051327');
    }

    public function benchCreateFromString(): void
    {
        $this->factory->createFromString('ffffffff-ffff-6fff-8fff-ffffffffffff');
    }
}
