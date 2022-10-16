<?php

declare(strict_types=1);

namespace Ramsey\Bench\Identifier\Uuid;

use Ramsey\Identifier\Uuid\UuidV3Factory;
use Ramsey\Identifier\Uuid\UuidV4Factory;
use Ramsey\Identifier\UuidIdentifier;

final class UuidV3FactoryBench
{
    private UuidV3Factory $factory;
    private UuidIdentifier $namespace;

    public function __construct()
    {
        $this->namespace = (new UuidV4Factory())->create();
        $this->factory = new UuidV3Factory();
    }

    public function benchCreate(): void
    {
        $this->factory->create($this->namespace, 'https://en.wikipedia.org/wiki/Universally_unique_identifier');
    }

    public function benchCreateFromBytes(): void
    {
        $this->factory->createFromBytes("\xff\xff\xff\xff\xff\xff\x3f\xff\x8f\xff\xff\xff\xff\xff\xff\xff");
    }

    public function benchCreateFromHexadecimal(): void
    {
        $this->factory->createFromHexadecimal('ffffffffffff3fff8fffffffffffffff');
    }

    public function benchCreateFromInteger(): void
    {
        $this->factory->createFromInteger('340282366920937556764398210441459793919');
    }

    public function benchCreateFromString(): void
    {
        $this->factory->createFromString('ffffffff-ffff-3fff-8fff-ffffffffffff');
    }
}
