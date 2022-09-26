<?php

/**
 * This file is part of ramsey/identifier
 *
 * ramsey/identifier is open source software: you can distribute
 * it and/or modify it under the terms of the MIT License
 * (the "License"). You may not use this file except in
 * compliance with the License.
 *
 * @copyright Copyright (c) Ben Ramsey <ben@benramsey.com>
 * @license https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Ramsey\Identifier\Uuid\Factory;

use Exception;
use Identifier\Uuid\UuidFactoryInterface;
use Identifier\Uuid\Version;
use Ramsey\Identifier\Service\Random\RandomBytesService;
use Ramsey\Identifier\Service\Random\RandomServiceInterface;
use Ramsey\Identifier\Uuid\Util;
use Ramsey\Identifier\Uuid\UuidV4;

/**
 * A factory for creating version 4, random UUIDs
 */
final class UuidV4Factory implements UuidFactoryInterface
{
    use DefaultFactory;

    public function __construct(
        private readonly RandomServiceInterface $randomService = new RandomBytesService(),
    ) {
    }

    /**
     * @throws Exception If a suitable source of randomness is not available
     */
    public function create(): UuidV4
    {
        $bytes = $this->randomService->getRandomBytes(16);
        $bytes = Util::applyVersionAndVariant($bytes, Version::Random);

        return new UuidV4($bytes);
    }

    public function createFromBytes(string $identifier): UuidV4
    {
        /** @var UuidV4 */
        return $this->createFromBytesInternal($identifier);
    }

    public function createFromHexadecimal(string $identifier): UuidV4
    {
        /** @var UuidV4 */
        return $this->createFromHexadecimalInternal($identifier);
    }

    public function createFromInteger(int | string $identifier): UuidV4
    {
        /** @var UuidV4 */
        return $this->createFromIntegerInternal($identifier);
    }

    public function createFromString(string $identifier): UuidV4
    {
        /** @var UuidV4 */
        return $this->createFromStringInternal($identifier);
    }

    /**
     * @psalm-mutation-free
     */
    protected function getVersion(): Version
    {
        return Version::Random;
    }

    protected function getUuidClass(): string
    {
        return UuidV4::class;
    }
}