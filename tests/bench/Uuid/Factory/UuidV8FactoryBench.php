<?php

declare(strict_types=1);

namespace Ramsey\Bench\Identifier\Uuid\Factory;

use Ramsey\Identifier\Uuid\Factory\UuidV8Factory;

final class UuidV8FactoryBench
{
    private UuidV8Factory $factory;

    public function __construct()
    {
        $this->factory = new UuidV8Factory();
    }

    public function benchCreate(): void
    {
        $this->factory->create('0123456789ab', 'abc', 'abcdef0123456789');
    }

    public function benchCreateFromBytes(): void
    {
        $this->factory->createFromBytes("\xff\xff\xff\xff\xff\xff\x8f\xff\x8f\xff\xff\xff\xff\xff\xff\xff");
    }

    public function benchCreateFromHexadecimal(): void
    {
        $this->factory->createFromHexadecimal('ffffffffffff8fff8fffffffffffffff');
    }

    public function benchCreateFromInteger(): void
    {
        $this->factory->createFromInteger('340282366920937934553716840013076889599');
    }

    public function benchCreateFromString(): void
    {
        $this->factory->createFromString('ffffffff-ffff-8fff-8fff-ffffffffffff');
    }
}
