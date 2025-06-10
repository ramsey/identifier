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

use Psr\SimpleCache\CacheException;
use Psr\SimpleCache\CacheInterface;
use Ramsey\Identifier\Exception\MacAddressNotFound;
use Ramsey\Identifier\Service\Os\Os;
use Ramsey\Identifier\Service\Os\PhpOs;

use function preg_match;
use function preg_match_all;
use function str_replace;
use function trim;

use const PREG_PATTERN_ORDER;

/**
 * A NIC that attempts to retrieve a MAC address from the system.
 */
final class SystemNic implements Nic
{
    /**
     * Pattern to match addresses in ifconfig and ipconfig output.
     */
    private const IFCONFIG_PATTERN = '/[^:]([0-9a-f]{2}([:-])[0-9a-f]{2}(\2[0-9a-f]{2}){4})[^:]/i';

    /**
     * Pattern to match addresses in sysfs stream output.
     */
    private const SYSFS_PATTERN = '/^([0-9a-f]{2}:){5}[0-9a-f]{2}$/i';

    /**
     * The cache key is generated from the Adler-32 checksum of this class name.
     *
     * ```
     * hash('adler32', SystemNic::class);
     * ```
     */
    private const CACHE_KEY = '__ramsey_id_34f20f6f';

    /**
     * The system address, stored statically for better performance.
     *
     * @var non-empty-string | null
     */
    private static ?string $address = null;

    /**
     * @param CacheInterface | null $cache An optional PSR-16 cache instance to cache the system address for faster
     *     lookups. Be aware that use of a centralized cache might have unintended consequences if you wish to use
     *     machine-specific addresses. If you wish for machine-specific addresses, use of a machine-local cache, such as
     *     APCu, is preferable.
     */
    public function __construct(
        private readonly ?CacheInterface $cache = null,
        private readonly Os $os = new PhpOs(),
    ) {
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
            $address = $this->getAddressFromSystem();
            $this->cache?->set(self::CACHE_KEY, $address);
        }

        return $address;
    }

    /**
     * Returns the system address if it can find it.
     *
     * @return non-empty-string
     */
    private function getAddressFromSystem(): string
    {
        $address = $this->getSysfs();

        if ($address === '') {
            $address = $this->getIfconfig();
        }

        if ($address === '') {
            // If all else fails, generate a random MAC address.
            $address = (new RandomNic())->address();
        }

        /** @var non-empty-string */
        return str_replace([':', '-'], '', $address);
    }

    /**
     * Returns the MAC address from the first system interface via ifconfig, ipconfig, or netstat.
     */
    private function getIfconfig(): string
    {
        $command = match ($this->os->getOsFamily()) {
            'Windows' => 'ipconfig /all',
            'Darwin' => 'ifconfig',
            'BSD' => 'netstat -i -f link',
            default => 'netstat -ie',
        };

        $ifconfig = $this->os->run($command);
        preg_match_all(self::IFCONFIG_PATTERN, $ifconfig, $matches, PREG_PATTERN_ORDER);

        foreach ($matches[1] as $address) {
            if ($address !== '00:00:00:00:00:00' && $address !== '00-00-00-00-00-00') {
                return $address;
            }
        }

        return '';
    }

    /**
     * Returns the MAC address from the first system interface via the sysfs interface.
     */
    private function getSysfs(): string
    {
        if ($this->os->getOsFamily() !== 'Linux') {
            return '';
        }

        foreach ($this->os->glob('/sys/class/net/*/address') as $path) {
            if ($this->os->isReadable($path)) {
                $address = trim($this->os->fileGetContents($path));
                if ($address !== '00:00:00:00:00:00' && preg_match(self::SYSFS_PATTERN, $address)) {
                    return $address;
                }
            }
        }

        return '';
    }
}
