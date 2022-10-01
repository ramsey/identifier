<?php

declare(strict_types=1);

namespace Ramsey\Bench\Identifier\Ulid\Factory;

use Ramsey\Identifier\Ulid\Factory\UlidFactory;

final class UlidFactoryBench
{
    private UlidFactory $factory;

    public function __construct()
    {
        $this->factory = new UlidFactory();
    }

    public function benchCreate(): void
    {
        $this->factory->create();
    }

    public function benchCreateFromBytes(): void
    {
        $this->factory->createFromBytes("\x01\x83\x95\xd2\x23\x7e\x49\xe9\xbb\x1c\xdf\xee\x6e\xd1\x8c\x60");
    }

    public function benchCreateFromHexadecimal(): void
    {
        $this->factory->createFromHexadecimal('018395d2237e49e9bb1cdfee6ed18c60');
    }

    public function benchCreateFromInteger(): void
    {
        $this->factory->createFromInteger('2012457612182699427991401803480665184');
    }

    public function benchCreateFromString(): void
    {
        $this->factory->createFromString('01GEAX48VY97MVP76ZXSQD3330');
    }
}
