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

namespace Ramsey\Identifier\Service\DceSecurity;

use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException as CacheInvalidArgumentException;
use Ramsey\Identifier\Exception\CacheException;
use Ramsey\Identifier\Exception\DceSecurityException;

use function escapeshellarg;
use function ini_get;
use function preg_split;
use function shell_exec;
use function sprintf;
use function str_contains;
use function str_getcsv;
use function strrpos;
use function strtolower;
use function substr;
use function trim;

use const PHP_OS_FAMILY;
use const PREG_SPLIT_NO_EMPTY;

/**
 * A service that retrieves person, group, and organization IDs from the system
 *
 * Organization IDs cannot be retrieved from the system. If using these, you
 * must provide a custom organization ID upon instantiation.
 */
final class SystemDceSecurityService implements DceSecurityServiceInterface
{
    /**
     * Key to use when caching the GID value in a PSR-16 cache instance
     */
    private const GID_CACHE_KEY = self::class . '::$groupId';

    /**
     * Key to use when caching the UID value in a PSR-16 cache instance
     */
    private const UID_CACHE_KEY = self::class . '::$personId';

    /**
     * @var int<0, max> | null
     */
    private static ?int $groupId = null;

    /**
     * @var int<0, max> | null
     */
    private static ?int $personId = null;

    /**
     * @param int<0, max> | null $orgId An organization ID must be
     *     provided if using the {@see self::getOrgIdentifier()} method.
     * @param CacheInterface | null $cache An optional PSR-16 cache instance to
     *     cache the system IDs for faster lookups. Be aware that use of a
     *     centralized cache might have unintended consequences if you wish to
     *     use machine-specific IDs. If you wish for machine-specific IDs,
     *     use of a machine-local cache, such as APCu, is preferable.
     */
    public function __construct(
        private readonly ?int $orgId = null,
        private readonly ?CacheInterface $cache = null,
    ) {
    }

    /**
     * @throws DceSecurityException if unable to obtain a system group ID
     * @throws CacheException if a problem occurs when fetching data from the
     *     PSR-16 cache instance, if provided
     */
    public function getGroupIdentifier(): int
    {
        if (self::$groupId === null) {
            $groupId = $this->getSystemGidFromCache();

            if ($groupId === null) {
                throw new DceSecurityException(
                    'Unable to get a group identifier using the system DCE '
                    . 'Security service; please provide a custom identifier or use '
                    . 'a different DCE service class',
                );
            }

            self::$groupId = $groupId;
        }

        return self::$groupId;
    }

    /**
     * @throws DceSecurityException if an org identifier was not provided upon instantiation
     */
    public function getOrgIdentifier(): int
    {
        if ($this->orgId === null) {
            throw new DceSecurityException(sprintf(
                'To use the org identifier, you must set $orgId when instantiating %s',
                self::class,
            ));
        }

        return $this->orgId;
    }

    /**
     * @throws DceSecurityException if unable to obtain a system person ID
     * @throws CacheException if a problem occurs when fetching data from the
     *     PSR-16 cache instance, if provided
     */
    public function getPersonIdentifier(): int
    {
        if (self::$personId === null) {
            $personId = $this->getSystemUidFromCache();

            if ($personId === null) {
                throw new DceSecurityException(
                    'Unable to get a person identifier using the system DCE '
                    . 'Security service; please provide a custom identifier or use '
                    . 'a different DCE service class',
                );
            }

            self::$personId = $personId;
        }

        return self::$personId;
    }

    /**
     * @return int<0, max> | null
     */
    private function getPosixGid(): ?int
    {
        /** @psalm-suppress ForbiddenCode */
        $gid = trim((string) shell_exec('id -g'));

        /** @var int<0, max> | null */
        return $gid === '' ? null : (int) $gid;
    }

    /**
     * @return int<0, max> | null
     */
    private function getPosixUid(): ?int
    {
        /** @psalm-suppress ForbiddenCode */
        $uid = trim((string) shell_exec('id -u'));

        /** @var int<0, max> | null */
        return $uid === '' ? null : (int) $uid;
    }

    /**
     * @return int<0, max> | null
     */
    private function getSystemGid(): ?int
    {
        if (!$this->hasShellExec()) {
            return null; // @codeCoverageIgnore
        }

        return match (PHP_OS_FAMILY) {
            'Windows' => $this->getWindowsGid(),
            default => $this->getPosixGid(),
        };
    }

    /**
     * @return int<0, max> | null
     *
     * @throws CacheException
     */
    private function getSystemGidFromCache(): ?int
    {
        try {
            /**
             * The return value of -1 is useful for testing purposes.
             *
             * @var int<-1, max> | null $gid
             */
            $gid = $this->cache?->get(self::GID_CACHE_KEY);

            if ($gid === null) {
                $gid = $this->getSystemGid();
                $this->cache?->set(self::GID_CACHE_KEY, $gid);
            }
        } catch (CacheInvalidArgumentException $exception) {
            throw new CacheException(
                sprintf('A problem occurred when attempting to use the cache key "%s"', self::GID_CACHE_KEY),
                $exception->getCode(),
                $exception,
            );
        }

        return $gid >= 0 ? $gid : null;
    }

    /**
     * @return int<0, max> | null
     */
    private function getSystemUid(): ?int
    {
        if (!$this->hasShellExec()) {
            return null; // @codeCoverageIgnore
        }

        return match (PHP_OS_FAMILY) {
            'Windows' => $this->getWindowsUid(),
            default => $this->getPosixUid(),
        };
    }

    /**
     * @return int<0, max> | null
     *
     * @throws CacheException
     */
    private function getSystemUidFromCache(): ?int
    {
        try {
            /**
             * The return value of -1 is useful for testing purposes.
             *
             * @var int<-1, max> | null $uid
             */
            $uid = $this->cache?->get(self::UID_CACHE_KEY);

            if ($uid === null) {
                $uid = $this->getSystemUid();
                $this->cache?->set(self::UID_CACHE_KEY, $uid);
            }
        } catch (CacheInvalidArgumentException $exception) {
            throw new CacheException(
                sprintf('A problem occurred when attempting to use the cache key "%s"', self::UID_CACHE_KEY),
                $exception->getCode(),
                $exception,
            );
        }

        return $uid >= 0 ? $uid : null;
    }

    /**
     * Returns a group identifier for a user on a Windows system
     *
     * Since Windows does not have the same concept as an effective POSIX GID
     * for the running script, we will get the local group memberships for the
     * user running the script. Then, we will get the SID (security identifier)
     * for the first group that appears in that list. Finally, we will return
     * the RID (relative identifier) for the group and use that as the GID.
     *
     * @link https://www.windows-commandline.com/list-of-user-groups-command-line/ List of user groups command line
     *
     * @return int<0, max> | null
     */
    private function getWindowsGid(): ?int
    {
        /** @psalm-suppress ForbiddenCode */
        $response = shell_exec('net user %username% | findstr /b /i "Local Group Memberships"');

        if ($response === null || $response === false) {
            return null; // @codeCoverageIgnore
        }

        /** @var string[] $userGroups */
        $userGroups = preg_split('/\s{2,}/', $response, -1, PREG_SPLIT_NO_EMPTY);

        $firstGroup = trim($userGroups[1] ?? '', "* \t\n\r\0\x0B");

        if ($firstGroup === '') {
            return null; // @codeCoverageIgnore
        }

        /** @psalm-suppress ForbiddenCode */
        $response = shell_exec('wmic group get name,sid | findstr /b /i ' . escapeshellarg($firstGroup));

        if ($response === null || $response === false) {
            return null; // @codeCoverageIgnore
        }

        /** @var string[] $userGroup */
        $userGroup = preg_split('/\s{2,}/', $response, -1, PREG_SPLIT_NO_EMPTY);

        $sid = $userGroup[1] ?? '';

        if (($lastHyphen = strrpos($sid, '-')) === false) {
            return null; // @codeCoverageIgnore
        }

        /** @var int<0, max> */
        return (int) trim(substr($sid, $lastHyphen + 1));
    }

    /**
     * Returns the user identifier for a user on a Windows system
     *
     * Windows does not have the same concept as an effective POSIX UID for the
     * running script. Instead, each user is uniquely identified by an SID
     * (security identifier). The SID includes three 32-bit unsigned integers
     * that make up a unique domain identifier, followed by an RID (relative
     * identifier) that we will use as the UID. The primary caveat is that this
     * UID may not be unique to the system, since it is, instead, unique to the
     * domain.
     *
     * @link https://www.lifewire.com/what-is-an-sid-number-2626005 What Is an SID Number?
     * @link https://bit.ly/30vE7NM Well-known SID Structures
     * @link https://bit.ly/2FWcYKJ Well-known security identifiers in Windows operating systems
     * @link https://www.windows-commandline.com/get-sid-of-user/ Get SID of user
     *
     * @return int<0, max> | null
     */
    private function getWindowsUid(): ?int
    {
        /** @psalm-suppress ForbiddenCode */
        $response = shell_exec('whoami /user /fo csv /nh');

        if ($response === null) {
            return null; // @codeCoverageIgnore
        }

        $sid = str_getcsv(trim((string) $response))[1] ?? '';

        if (($lastHyphen = strrpos($sid, '-')) === false) {
            return null; // @codeCoverageIgnore
        }

        /** @var int<0, max> */
        return (int) trim(substr($sid, $lastHyphen + 1));
    }

    /**
     * Returns true if shell_exec() is available for use
     */
    private function hasShellExec(): bool
    {
        $disabledFunctions = strtolower((string) ini_get('disable_functions'));

        return !str_contains($disabledFunctions, 'shell_exec');
    }
}
