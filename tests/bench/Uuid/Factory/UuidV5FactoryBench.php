<?php

declare(strict_types=1);

namespace Ramsey\Bench\Identifier\Uuid\Factory;

use Ramsey\Identifier\Uuid\Factory\UuidV4Factory;
use Ramsey\Identifier\Uuid\Factory\UuidV5Factory;
use Ramsey\Identifier\Uuid\Uuid;

final class UuidV5FactoryBench
{
    private UuidV5Factory $factory;
    private Uuid $namespace;

    public function __construct()
    {
        $this->namespace = (new UuidV4Factory())->create();
        $this->factory = new UuidV5Factory();
    }

    public function benchCreate(): void
    {
        $this->factory->create($this->namespace, 'https://en.wikipedia.org/wiki/Universally_unique_identifier');
    }

    public function benchCreateFromBytes(): void
    {
        $this->factory->createFromBytes("\xff\xff\xff\xff\xff\xff\x5f\xff\x8f\xff\xff\xff\xff\xff\xff\xff");
    }

    public function benchCreateFromHexadecimal(): void
    {
        $this->factory->createFromHexadecimal('ffffffffffff5fff8fffffffffffffff');
    }

    public function benchCreateFromInteger(): void
    {
        $this->factory->createFromInteger('340282366920937707880125662270106632191');
    }

    public function benchCreateFromString(): void
    {
        $this->factory->createFromString('ffffffff-ffff-5fff-8fff-ffffffffffff');
    }
}
