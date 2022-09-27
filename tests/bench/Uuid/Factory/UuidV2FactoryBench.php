<?php

declare(strict_types=1);

namespace Ramsey\Bench\Identifier\Uuid\Factory;

use Ramsey\Identifier\Uuid\Factory\UuidV2Factory;

final class UuidV2FactoryBench
{
    private UuidV2Factory $factory;

    public function __construct()
    {
        $this->factory = new UuidV2Factory();
    }

    public function benchCreate(): void
    {
        $this->factory->create();
    }

    public function benchCreateFromBytes(): void
    {
        $this->factory->createFromBytes("\xff\xff\xff\xff\xff\xff\x2f\xff\x8f\x00\xff\xff\xff\xff\xff\xff");
    }

    public function benchCreateFromHexadecimal(): void
    {
        $this->factory->createFromHexadecimal('ffffffffffff2fff8f00ffffffffffff');
    }

    public function benchCreateFromInteger(): void
    {
        $this->factory->createFromInteger('340282366920937481206463271358028578815');
    }

    public function benchCreateFromString(): void
    {
        $this->factory->createFromString('ffffffff-ffff-2fff-8f00-ffffffffffff');
    }
}
