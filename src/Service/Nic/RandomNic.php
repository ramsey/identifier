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

namespace Ramsey\Identifier\Service\Nic;

use Psr\SimpleCache\CacheInterface;

use function random_int;
use function sprintf;

/**
 * A NIC that generates a random MAC address and sets the multicast bit,
 * according to RFC 4122, section 4.5. The address is stored and reused for each
 * call to address() within the same process. If a cache is provided, the same
 * address is used across processes.
 *
 * @link https://www.rfc-editor.org/rfc/rfc4122.html#section-4.5 RFC 4122: Node IDs that Do Not Identify the Host
 * @link https://www.ietf.org/archive/id/draft-ietf-uuidrev-rfc4122bis-00.html#section-6.9 rfc4122bis: UUIDs that Do Not Identify the Host
 */
final class RandomNic implements Nic
{
    /**
     * Key to use when caching the address value in a PSR-16 cache instance
     */
    private const CACHE_KEY = '__ramsey_id_random_addr';

    /**
     * The address, stored statically for better performance
     *
     * @var non-empty-string | null
     */
    private static ?string $address = null;

    /**
     * @param CacheInterface | null $cache An optional PSR-16 cache instance to
     *     cache the address for faster lookups. Be aware that use of a
     *     centralized cache might have unintended consequences if you wish to
     *     use machine-specific addresses. If you wish for machine-specific
     *     addresses, use of a machine-local cache, such as APCu, is preferable.
     */
    public function __construct(private readonly ?CacheInterface $cache = null)
    {
    }

    public function address(): string
    {
        if (self::$address === null) {
            self::$address = $this->getAddressFromCache();
        }

        return self::$address;
    }

    /**
     * @return non-empty-string
     */
    private function getAddressFromCache(): string
    {
        /** @var string | null $address */
        $address = $this->cache?->get(self::CACHE_KEY);

        if ($address === null || $address === '') {
            $address = $this->generateAddress();
            $this->cache?->set(self::CACHE_KEY, $address);
        }

        return $address;
    }

    /**
     * @return non-empty-string
     */
    public function generateAddress(): string
    {
        /** @var non-empty-string */
        return sprintf('%012x', random_int(0, 0xffffffffffff) | 0x010000000000);
    }
}
