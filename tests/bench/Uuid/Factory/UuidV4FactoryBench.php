<?php

declare(strict_types=1);

namespace Ramsey\Bench\Identifier\Uuid\Factory;

use Ramsey\Identifier\Uuid\Factory\UuidV4Factory;

final class UuidV4FactoryBench
{
    private UuidV4Factory $factory;

    public function __construct()
    {
        $this->factory = new UuidV4Factory();
    }

    public function benchCreate(): void
    {
        $this->factory->create();
    }

    public function benchCreateFromBytes(): void
    {
        $this->factory->createFromBytes("\xff\xff\xff\xff\xff\xff\x4f\xff\x8f\xff\xff\xff\xff\xff\xff\xff");
    }

    public function benchCreateFromHexadecimal(): void
    {
        $this->factory->createFromHexadecimal('ffffffffffff4fff8fffffffffffffff');
    }

    public function benchCreateFromInteger(): void
    {
        $this->factory->createFromInteger('340282366920937632322261936355783213055');
    }

    public function benchCreateFromString(): void
    {
        $this->factory->createFromString('ffffffff-ffff-4fff-8fff-ffffffffffff');
    }
}
