<?php

declare(strict_types=1);

namespace Ramsey\Bench\Identifier\Uuid\Factory;

use Ramsey\Identifier\Uuid\Factory\UuidV1Factory;

final class UuidV1FactoryBench
{
    private UuidV1Factory $factory;

    public function __construct()
    {
        $this->factory = new UuidV1Factory();
    }

    public function benchCreate(): void
    {
        $this->factory->create();
    }

    public function benchCreateFromBytes(): void
    {
        $this->factory->createFromBytes("\xff\xff\xff\xff\xff\xff\x1f\xff\x8f\xff\xff\xff\xff\xff\xff\xff");
    }

    public function benchCreateFromHexadecimal(): void
    {
        $this->factory->createFromHexadecimal('ffffffffffff1fff8fffffffffffffff');
    }

    public function benchCreateFromInteger(): void
    {
        $this->factory->createFromInteger('340282366920937405648670758612812955647');
    }

    public function benchCreateFromString(): void
    {
        $this->factory->createFromString('ffffffff-ffff-1fff-8fff-ffffffffffff');
    }
}
