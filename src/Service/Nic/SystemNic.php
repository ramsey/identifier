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
use Psr\SimpleCache\InvalidArgumentException as CacheInvalidArgumentException;
use Ramsey\Identifier\Exception\InvalidCacheKey;
use Ramsey\Identifier\Exception\MacAddressNotFound;

use function fgets;
use function file_get_contents;
use function glob;
use function ini_get;
use function is_readable;
use function pclose;
use function popen;
use function preg_match;
use function sprintf;
use function str_contains;
use function str_replace;
use function strtolower;
use function trim;

use const GLOB_NOSORT;
use const PHP_OS_FAMILY;

/**
 * A NIC that attempts to retrieve a MAC address from the system
 */
final class SystemNic implements Nic
{
    /**
     * Pattern to match addresses in ifconfig and ipconfig output
     */
    private const IFCONFIG_PATTERN = '/[^:]([0-9a-f]{2}([:-])[0-9a-f]{2}(\2[0-9a-f]{2}){4})[^:]/i';

    /**
     * Pattern to match addresses in sysfs stream output
     */
    private const SYSFS_PATTERN = '/^([0-9a-f]{2}:){5}[0-9a-f]{2}$/i';

    /**
     * Key to use when caching the address value in a PSR-16 cache instance
     */
    private const CACHE_KEY = self::class . '::$address';

    /**
     * The system address, stored statically for better performance
     */
    private static ?string $address = null;

    /**
     * @param CacheInterface | null $cache An optional PSR-16 cache instance to
     *     cache the system address for faster lookups. Be aware that use of a
     *     centralized cache might have unintended consequences if you wish to
     *     use machine-specific addresses. If you wish for machine-specific
     *     addresses, use of a machine-local cache, such as APCu, is preferable.
     */
    public function __construct(private readonly ?CacheInterface $cache = null)
    {
    }

    /**
     * @throws MacAddressNotFound
     * @throws InvalidCacheKey if a problem occurs when fetching data from the
     *     PSR-16 cache instance, if provided
     */
    public function address(): string
    {
        if (self::$address === null) {
            self::$address = $this->getAddressFromCache();
        }

        if (self::$address === '') {
            throw new MacAddressNotFound('Unable to fetch an address for this system');
        }

        return self::$address;
    }

    /**
     * @throws InvalidCacheKey when a problem occurs with the cache key
     */
    private function getAddressFromCache(): string
    {
        try {
            /** @var string | null $address */
            $address = $this->cache?->get(self::CACHE_KEY);

            if ($address === null) {
                $address = $this->getAddressFromSystem();
                $this->cache?->set(self::CACHE_KEY, $address);
            }
        } catch (CacheInvalidArgumentException $exception) {
            throw new InvalidCacheKey(
                sprintf('A problem occurred when attempting to use the cache key "%s"', self::CACHE_KEY),
                $exception->getCode(),
                $exception,
            );
        }

        return $address;
    }

    /**
     * Returns the system address, if it can find it
     */
    private function getAddressFromSystem(): string
    {
        $address = $this->getSysfs();

        if ($address === '') {
            $address = $this->getIfconfig();
        }

        return str_replace([':', '-'], '', $address);
    }

    /**
     * Returns the MAC address from the first system interface via ifconfig, ipconfig, or netstat
     */
    private function getIfconfig(): string
    {
        $disabledFunctions = strtolower((string) ini_get('disable_functions'));

        if (str_contains($disabledFunctions, 'popen')) {
            return ''; // @codeCoverageIgnore
        }

        $command = match (PHP_OS_FAMILY) {
            'Windows' => 'ipconfig /all 2>&1',
            'Darwin' => 'ifconfig 2>&1',
            'BSD' => 'netstat -i -f link 2>&1',
            default => 'netstat -ie 2>&1', // @codeCoverageIgnore
        };

        $process = popen($command, 'r');
        if ($process === false) {
            return ''; // @codeCoverageIgnore
        }

        $address = '';
        while (($buffer = fgets($process)) !== false) {
            if (preg_match(self::IFCONFIG_PATTERN, $buffer, $matches)) {
                $address = $matches[1];

                break;
            }
        }

        pclose($process);

        return $address;
    }

    /**
     * Returns the MAC address from the first system interface via the sysfs interface
     */
    private function getSysfs(): string
    {
        if (PHP_OS_FAMILY !== 'Linux') {
            return '';
        }

        $paths = glob('/sys/class/net/*/address', GLOB_NOSORT) ?: [];

        /** @var string[] $macs */
        $macs = [];

        foreach ($paths as $path) {
            if (is_readable($path)) {
                $address = trim((string) file_get_contents($path));
                if ($address !== '00:00:00:00:00:00' && preg_match(self::SYSFS_PATTERN, $address)) {
                    $macs[] = $address;
                }
            }
        }

        return $macs[0] ?? '';
    }
}
