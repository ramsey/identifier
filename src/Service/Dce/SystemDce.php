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

namespace Ramsey\Identifier\Service\Dce;

use Psr\SimpleCache\CacheException;
use Psr\SimpleCache\CacheInterface;
use Ramsey\Identifier\Exception\DceIdentifierNotFound;
use Ramsey\Identifier\Service\Os\Os;
use Ramsey\Identifier\Service\Os\PhpOs;

use function escapeshellarg;
use function preg_split;
use function sprintf;
use function str_getcsv;
use function strrpos;
use function substr;
use function trim;

use const PREG_SPLIT_NO_EMPTY;

/**
 * Retrieves user, group, and organization IDs from the system for generating
 * DCE Security (version 2) UUIDs
 *
 * Organization IDs cannot be retrieved from the system. If using these, you
 * must provide a custom organization ID upon instantiation.
 */
final class SystemDce implements Dce
{
    /**
     * Key to use when caching the GID value in a PSR-16 cache instance
     */
    private const GID_CACHE_KEY = '__ramsey_id_system_dce_gid';

    /**
     * Key to use when caching the UID value in a PSR-16 cache instance
     */
    private const UID_CACHE_KEY = '__ramsey_id_system_dce_uid';

    /**
     * @var int<0, max> | null
     */
    private static ?int $groupId = null;

    /**
     * @var int<0, max> | null
     */
    private static ?int $userId = null;

    /**
     * @param int<0, max> | null $orgId An organization ID must be
     *     provided if using the {@see self::orgId()} method.
     * @param CacheInterface | null $cache An optional PSR-16 cache instance to
     *     cache the system IDs for faster lookups. Be aware that use of a
     *     centralized cache might have unintended consequences if you wish to
     *     use machine-specific IDs. If you wish for machine-specific IDs,
     *     use of a machine-local cache, such as APCu, is preferable.
     */
    public function __construct(
        private readonly ?int $orgId = null,
        private readonly ?CacheInterface $cache = null,
        private readonly Os $os = new PhpOs(),
    ) {
    }

    /**
     * @throws DceIdentifierNotFound if unable to obtain a system group ID
     */
    public function groupId(): int
    {
        if (self::$groupId === null) {
            try {
                $groupId = $this->getSystemGidFromCache();
            } catch (CacheException $cacheException) {
                $groupId = null;
            }

            if ($groupId === null) {
                throw new DceIdentifierNotFound(
                    message: 'Unable to get a group identifier using the system DCE '
                        . 'service; please provide a custom identifier or use '
                        . 'a different DCE service class',
                    previous: $cacheException ?? null,
                );
            }

            self::$groupId = $groupId;
        }

        return self::$groupId;
    }

    /**
     * @throws DceIdentifierNotFound if an org identifier was not provided upon instantiation
     */
    public function orgId(): int
    {
        if ($this->orgId === null) {
            throw new DceIdentifierNotFound(sprintf(
                'To use the org identifier, you must set $orgId when instantiating %s',
                self::class,
            ));
        }

        return $this->orgId;
    }

    /**
     * @throws DceIdentifierNotFound if unable to obtain a system user ID
     */
    public function userId(): int
    {
        if (self::$userId === null) {
            try {
                $userId = $this->getSystemUidFromCache();
            } catch (CacheException $cacheException) {
                $userId = null;
            }

            if ($userId === null) {
                throw new DceIdentifierNotFound(
                    message: 'Unable to get a user identifier using the system DCE '
                        . 'service; please provide a custom identifier or use '
                        . 'a different DCE service class',
                    previous: $cacheException ?? null,
                );
            }

            self::$userId = $userId;
        }

        return self::$userId;
    }

    /**
     * @return int<0, max> | null
     */
    private function getPosixGid(): ?int
    {
        $gid = trim($this->os->run('id -g'));

        /** @var int<0, max> | null */
        return $gid === '' ? null : (int) $gid;
    }

    /**
     * @return int<0, max> | null
     */
    private function getPosixUid(): ?int
    {
        $uid = trim($this->os->run('id -u'));

        /** @var int<0, max> | null */
        return $uid === '' ? null : (int) $uid;
    }

    /**
     * @return int<0, max> | null
     */
    private function getSystemGid(): ?int
    {
        return match ($this->os->getOsFamily()) {
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

        return $gid >= 0 ? $gid : null;
    }

    /**
     * @return int<0, max> | null
     */
    private function getSystemUid(): ?int
    {
        return match ($this->os->getOsFamily()) {
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
        $response = $this->os->run('net user %username% | findstr /b /i "Local Group Memberships"');

        if ($response === '') {
            return null;
        }

        /** @var list<string> $userGroups */
        $userGroups = preg_split('/\s{2,}/', $response, -1, PREG_SPLIT_NO_EMPTY);

        $firstGroup = trim($userGroups[1] ?? '', "* \t\n\r\0\x0B");

        if ($firstGroup === '') {
            return null;
        }

        $response = $this->os->run('wmic group get name,sid | findstr /b /i ' . escapeshellarg($firstGroup));

        if ($response === '') {
            return null;
        }

        /** @var list<string> $userGroup */
        $userGroup = preg_split('/\s{2,}/', $response, -1, PREG_SPLIT_NO_EMPTY);

        $sid = $userGroup[1] ?? '';

        if (($lastHyphen = strrpos($sid, '-')) === false) {
            return null;
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
     * @link https://learn.microsoft.com/en-us/openspecs/windows_protocols/ms-dtyp/81d92bba-d22b-4a8c-908a-554ab29148ab Well-known SID Structures
     * @link https://learn.microsoft.com/en-us/windows-server/identity/ad-ds/manage/understand-security-identifiers Well-known security identifiers in Windows operating systems
     * @link https://www.windows-commandline.com/get-sid-of-user/ Get SID of user
     *
     * @return int<0, max> | null
     */
    private function getWindowsUid(): ?int
    {
        $response = $this->os->run('whoami /user /fo csv /nh');

        if ($response === '') {
            return null;
        }

        $sid = str_getcsv(string: trim($response), escape: '')[1] ?? '';

        if (($lastHyphen = strrpos($sid, '-')) === false) {
            return null;
        }

        /** @var int<0, max> */
        return (int) trim(substr($sid, $lastHyphen + 1));
    }
}
