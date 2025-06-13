<?php

/**
 * This file is part of ramsey/identifier
 *
 * ramsey/identifier is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser
 * General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.
 *
 * ramsey/identifier is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the
 * implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License
 * for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License along with ramsey/identifier. If not, see
 * <https://www.gnu.org/licenses/>.
 *
 * @copyright Copyright (c) Ben Ramsey <ben@ramsey.dev> and Contributors
 * @license https://opensource.org/license/lgpl-3-0/ GNU Lesser General Public License version 3 or later
 */

declare(strict_types=1);

namespace Ramsey\Identifier\Service\Nic;

use Psr\SimpleCache\CacheException;
use Psr\SimpleCache\CacheInterface;
use Ramsey\Identifier\Exception\MacAddressNotFound;

use function random_int;
use function sprintf;

/**
 * A NIC that generates a random MAC address and sets the multicast bit, according to RFC 9562, section 6.10. The
 * address is stored and reused for each call to address() within the same process. If a cache is provided, the same
 * address is used across processes.
 *
 * @link https://www.rfc-editor.org/rfc/rfc9562#section-6.10 RFC 9562, section 6.10. UUIDs That Do Not Identify the Host.
 */
final class RandomNic implements Nic
{
    /**
     * The cache key is generated from the Adler-32 checksum of this class name.
     *
     * ```
     * hash('adler32', RandomNic::class);
     * ```
     */
    private const CACHE_KEY = '__ramsey_id_33d80f4b';

    /**
     * The address, stored statically for better performance.
     *
     * @var non-empty-string | null
     */
    private static ?string $address = null;

    /**
     * @param CacheInterface | null $cache An optional PSR-16 cache instance to cache the address for faster lookups.
     *     Be aware that use of a centralized cache might have unintended consequences if you wish to use
     *     machine-specific addresses. If you wish for machine-specific addresses, use of a machine-local cache, such as
     *     APCu, is preferable.
     */
    public function __construct(private readonly ?CacheInterface $cache = null)
    {
    }

    public function address(): string
    {
        if (self::$address === null) {
            try {
                self::$address = $this->getAddressFromCache();
            } catch (CacheException $cacheException) {
                throw new MacAddressNotFound(
                    message: 'Unable to retrieve MAC address from cache',
                    previous: $cacheException,
                );
            }
        }

        return self::$address;
    }

    /**
     * @return non-empty-string
     *
     * @throws CacheException
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
    private function generateAddress(): string
    {
        /** @var non-empty-string */
        return sprintf('%012x', random_int(0, 0xffffffffffff) | 0x010000000000);
    }
}
