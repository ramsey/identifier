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

namespace Ramsey\Identifier\Service\Node;

use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException as CacheInvalidArgumentException;
use Ramsey\Identifier\Exception\CacheException;
use Ramsey\Identifier\Exception\NodeNotFoundException;

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
 * A node service that retrieves the system node ID, if possible
 *
 * The system node ID, or host ID, is often the same as the MAC address for a
 * network interface on the host.
 */
final class SystemNodeService implements NodeServiceInterface
{
    /**
     * Pattern to match nodes in ifconfig and ipconfig output
     */
    private const IFCONFIG_PATTERN = '/[^:]([0-9a-f]{2}([:-])[0-9a-f]{2}(\2[0-9a-f]{2}){4})[^:]/i';

    /**
     * Pattern to match nodes in sysfs stream output
     */
    private const SYSFS_PATTERN = '/^([0-9a-f]{2}:){5}[0-9a-f]{2}$/i';

    /**
     * Key to use when caching the node value in a PSR-16 cache instance
     */
    private const CACHE_KEY = self::class . '::$node';

    /**
     * The system node, stored statically for better performance
     */
    private static ?string $node = null;

    /**
     * @param CacheInterface | null $cache An optional PSR-16 cache instance to
     *     cache the system node for faster lookups. Be aware that use of a
     *     centralized cache might have unintended consequences if you wish to
     *     use machine-specific nodes. If you wish for machine-specific nodes,
     *     use of a machine-local cache, such as APCu, is preferable.
     */
    public function __construct(private readonly ?CacheInterface $cache = null)
    {
    }

    /**
     * @throws NodeNotFoundException when unable to find a node for the system
     */
    public function getNode(): string
    {
        if (self::$node === null) {
            self::$node = $this->getNodeFromCache();
        }

        if (self::$node === '') {
            throw new NodeNotFoundException('Unable to fetch a node for this system');
        }

        return self::$node;
    }

    /**
     * @throws CacheException when a problem occurs with the cache key
     */
    private function getNodeFromCache(): string
    {
        try {
            /** @var string | null $node */
            $node = $this->cache?->get(self::CACHE_KEY);

            if ($node === null) {
                $node = $this->getNodeFromSystem();
                $this->cache?->set(self::CACHE_KEY, $node);
            }
        } catch (CacheInvalidArgumentException $exception) {
            throw new CacheException(
                sprintf('A problem occurred when attempting to use the cache key "%s"', self::CACHE_KEY),
                $exception->getCode(),
                $exception,
            );
        }

        return $node;
    }

    /**
     * Returns the system node, if it can find it
     */
    private function getNodeFromSystem(): string
    {
        $node = $this->getSysfs();

        if ($node === '') {
            $node = $this->getIfconfig();
        }

        return str_replace([':', '-'], '', $node);
    }

    /**
     * Returns the MAC address from the first system interface via ifconfig, ipconfig, or netstat
     */
    private function getIfconfig(): string
    {
        $disabledFunctions = strtolower((string) ini_get('disable_functions'));

        // @codeCoverageIgnoreStart

        if (str_contains($disabledFunctions, 'popen')) {
            return '';
        }

        $command = match (PHP_OS_FAMILY) {
            'Windows' => 'ipconfig /all 2>&1',
            'Darwin' => 'ifconfig 2>&1',
            'BSD' => 'netstat -i -f link 2>&1',
            default => 'netstat -ie 2>&1',
        };

        $process = popen($command, 'r');
        if ($process === false) {
            return '';
        }

        // @codeCoverageIgnoreEnd

        $node = '';
        while (($buffer = fgets($process)) !== false) {
            if (preg_match(self::IFCONFIG_PATTERN, $buffer, $matches)) {
                $node = $matches[1];

                break;
            }
        }

        pclose($process);

        return $node;
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
