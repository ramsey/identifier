<?php

/**
 * This file is part of ramsey/identifier
 *
 * Copyright © Ben Ramsey <ben@ramsey.dev> and Contributors
 *
 * ramsey/identifier is free software: you can redistribute it and/or modify it
 * under the terms of the GNU Lesser General Public License as published by the
 * Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.
 *
 * ramsey/identifier is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License
 * for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with ramsey/identifier. If not, see <https://www.gnu.org/licenses/>.
 *
 * SPDX-License-Identifier: LGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Ramsey\Identifier\Service\Dce;

use Override;
use Psr\SimpleCache\CacheException;
use Psr\SimpleCache\CacheInterface;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Service\Os\MissingFunction;
use Ramsey\Identifier\Service\Os\Os;
use Ramsey\Identifier\Service\Os\PhpOs;

use function min;
use function preg_match;
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
     * The cache key is generated from the Adler-32 checksum of this class name
     * + "GID"
     *
     * ```
     * hash('adler32', SystemDce::class . 'GID');
     * ```
     */
    private const string GID_CACHE_KEY = '__ramsey_id_63ba1027';

    /**
     * The cache key is generated from the Adler-32 checksum of this class name
     * + "UID"
     *
     * ```
     * hash('adler32', SystemDce::class . 'UID');
     * ```
     */
    private const string UID_CACHE_KEY = '__ramsey_id_63e41035';

    /**
     * Pattern to match the SID of a built-in local group, S-1-5-32-RID, or of
     * a group of the machine or of a domain, S-1-5-21-X-Y-Z-RID
     *
     * Each number is written as Windows writes it, with no leading zero.
     */
    private const string WINDOWS_GROUP_SID_PATTERN = '/^S-1-5-(32|21(?:-(?:0|[1-9]\d*)){3})-(0|[1-9]\d{0,9})$/D';

    /**
     * @var int<0, 4294967295> | null
     */
    private static ?int $groupId = null;

    /**
     * @var int<0, 4294967295> | null
     */
    private static ?int $userId = null;

    /**
     * @param int<0, 4294967295> | null $orgId An organization ID must be
     *     provided if using the {@see self::orgId()} method
     * @param CacheInterface | null $cache An optional PSR-16 cache instance to
     *     cache the system IDs for faster lookups. Be aware that use of a
     *     centralized cache might have unintended consequences if you wish to
     *     use machine-specific IDs. If you wish for machine-specific IDs, use
     *     of a machine-local cache, such as APCu, is preferable.
     */
    public function __construct(
        private readonly ?int $orgId = null,
        private readonly ?CacheInterface $cache = null,
        private readonly Os $os = new PhpOs(),
    ) {
        if ($this->orgId !== null && ($this->orgId < 0 || $this->orgId > 0xffffffff)) {
            throw new InvalidArgument('The DCE org ID must be a positive 32-bit integer or null');
        }
    }

    /**
     * @throws DceIdentifierNotFound if unable to get a system group ID
     */
    #[Override]
    public function groupId(): int
    {
        if (self::$groupId === null) {
            try {
                $groupId = $this->getSystemGidFromCache();
            } catch (CacheException | MissingFunction $exception) {
                $groupId = null;
            }

            if ($groupId === null) {
                throw new DceIdentifierNotFound(
                    message: 'Unable to get a group identifier using the system DCE service; please provide a custom '
                        . 'identifier or use a different DCE service class',
                    previous: $exception ?? null,
                );
            }

            self::$groupId = $groupId;
        }

        return self::$groupId;
    }

    /**
     * @throws DceIdentifierNotFound if an org identifier was not provided upon
     *     instantiation
     */
    #[Override]
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
     * @throws DceIdentifierNotFound if unable to get a system user ID
     */
    #[Override]
    public function userId(): int
    {
        if (self::$userId === null) {
            try {
                $userId = $this->getSystemUidFromCache();
            } catch (CacheException | MissingFunction $exception) {
                $userId = null;
            }

            if ($userId === null) {
                throw new DceIdentifierNotFound(
                    message: 'Unable to get a user identifier using the system DCE service; please provide a custom '
                        . 'identifier or use a different DCE service class',
                    previous: $exception ?? null,
                );
            }

            self::$userId = $userId;
        }

        return self::$userId;
    }

    /**
     * @return int<0, 4294967295> | null
     */
    private function getPosixGid(): ?int
    {
        $gid = trim($this->os->run('id -g'));

        /** @var int<0, 4294967295> | null */
        return $gid === '' ? null : (int) $gid;
    }

    /**
     * @return int<0, 4294967295> | null
     */
    private function getPosixUid(): ?int
    {
        $uid = trim($this->os->run('id -u'));

        /** @var int<0, 4294967295> | null */
        return $uid === '' ? null : (int) $uid;
    }

    /**
     * @return int<0, 4294967295> | null
     */
    private function getSystemGid(): ?int
    {
        return match ($this->os->getOsFamily()) {
            'Windows' => $this->getWindowsGid(),
            default => $this->getPosixGid(),
        };
    }

    /**
     * @return int<0, 4294967295> | null
     *
     * @throws CacheException
     */
    private function getSystemGidFromCache(): ?int
    {
        /**
         * The return value of -1 is useful for testing purposes
         *
         * @var int<-1, 4294967295> | null $gid
         */
        $gid = $this->cache?->get(self::GID_CACHE_KEY);

        if ($gid === null) {
            $gid = $this->getSystemGid();
            $this->cache?->set(self::GID_CACHE_KEY, $gid);
        }

        return $gid >= 0 ? $gid : null;
    }

    /**
     * @return int<0, 4294967295> | null
     */
    private function getSystemUid(): ?int
    {
        return match ($this->os->getOsFamily()) {
            'Windows' => $this->getWindowsUid(),
            default => $this->getPosixUid(),
        };
    }

    /**
     * @return int<0, 4294967295> | null
     *
     * @throws CacheException
     */
    private function getSystemUidFromCache(): ?int
    {
        /**
         * The return value of -1 is useful for testing purposes
         *
         * @var int<-1, 4294967295> | null $uid
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
     * Windows does not have the same concept as an effective POSIX GID for the
     * running script. Instead, we list the groups of the user running the
     * script, each with its SID (security identifier), and use the RID
     * (relative identifier) of one group as the GID.
     *
     * A built-in local group, such as Users, has an SID of the form
     * S-1-5-32-RID, and its RID is the same on every Windows system. A group
     * of the machine or of a domain has an SID of the form
     * S-1-5-21-X-Y-Z-RID. We return the lowest RID among the built-in groups,
     * or, for a user in no built-in group, the lowest RID among the groups of
     * the machine or domain. The order in which Windows lists the groups has
     * no effect on the result.
     *
     * @link https://learn.microsoft.com/en-us/windows-server/administration/windows-commands/whoami whoami
     * @link https://learn.microsoft.com/en-us/windows-server/identity/ad-ds/manage/understand-security-identifiers#well-known-sids Well-known SIDs
     *
     * @return int<0, 4294967295> | null
     */
    private function getWindowsGid(): ?int
    {
        $response = $this->os->run('whoami /groups /fo csv /nh');

        $builtInRids = [];
        $otherRids = [];

        /** @var list<string> $lines */
        $lines = preg_split('/\r\n|\r|\n/', $response, -1, PREG_SPLIT_NO_EMPTY);

        foreach ($lines as $line) {
            $sid = str_getcsv(string: $line, escape: '')[2] ?? '';

            if (preg_match(self::WINDOWS_GROUP_SID_PATTERN, $sid, $matches) !== 1) {
                continue;
            }

            $rid = (int) $matches[2];

            if ($rid > 0xffffffff) {
                continue;
            }

            if ($matches[1] === '32') {
                $builtInRids[] = $rid;
            } else {
                $otherRids[] = $rid;
            }
        }

        $rids = $builtInRids !== [] ? $builtInRids : $otherRids;

        if ($rids === []) {
            return null;
        }

        /** @var int<0, 4294967295> */
        return min($rids);
    }

    /**
     * Returns the user identifier for a user on a Windows system
     *
     * Windows does not have the same concept as an effective POSIX UID for the
     * running script. Instead, each user is uniquely identified by an SID
     * (security identifier). The SID includes three 32-bit unsigned integers
     * that make up a unique domain identifier, followed by an RID (relative
     * identifier) that we will use as the UID. The primary issue is that this
     * UID may not be unique to the system, since it is, instead, unique to the
     * domain.
     *
     * @link https://www.lifewire.com/what-is-an-sid-number-2626005 What Is an SID Number?
     * @link https://learn.microsoft.com/en-us/openspecs/windows_protocols/ms-dtyp/81d92bba-d22b-4a8c-908a-554ab29148ab Well-known SID Structures
     * @link https://learn.microsoft.com/en-us/windows-server/identity/ad-ds/manage/understand-security-identifiers#well-known-sids Well-known SIDs
     * @link https://www.windows-commandline.com/get-sid-of-user/ Get SID of user
     *
     * @return int<0, 4294967295> | null
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

        /** @var int<0, 4294967295> */
        return (int) trim(substr($sid, $lastHyphen + 1));
    }
}
