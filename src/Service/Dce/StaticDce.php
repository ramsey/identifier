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

namespace Ramsey\Identifier\Service\Dce;

use Ramsey\Identifier\Exception\DceIdentifierNotFound;
use Ramsey\Identifier\Exception\InvalidArgument;

use function sprintf;

/**
 * Provides pre-determined user, group, and organization IDs for generating DCE Security (version 2) UUIDs.
 */
final readonly class StaticDce implements Dce
{
    /**
     * Constructs a static DCE service.
     *
     * Each parameter is optional, so you may only provide the one you intend to use. However, when attempting to use a
     * method for a property not provided, this class will throw a {@see DceIdentifierNotFound}.
     *
     * @param int<0, 4294967295> | null $userId An optional user identifier, or UID.
     * @param int<0, 4294967295> | null $groupId An optional group identifier, or GID.
     * @param int<0, 4294967295> | null $orgId An optional organization identifier.
     */
    public function __construct(
        private ?int $userId = null,
        private ?int $groupId = null,
        private ?int $orgId = null,
    ) {
        if ($this->userId !== null && ($this->userId < 0 || $this->userId > 0xffffffff)) {
            throw new InvalidArgument('The DCE user ID must be a positive 32-bit integer or null');
        }

        if ($this->groupId !== null && ($this->groupId < 0 || $this->groupId > 0xffffffff)) {
            throw new InvalidArgument('The DCE group ID must be a positive 32-bit integer or null');
        }

        if ($this->orgId !== null && ($this->orgId < 0 || $this->orgId > 0xffffffff)) {
            throw new InvalidArgument('The DCE org ID must be a positive 32-bit integer or null');
        }
    }

    /**
     * @throws DceIdentifierNotFound if a group identifier was not provided upon instantiation.
     */
    public function groupId(): int
    {
        if ($this->groupId === null) {
            throw new DceIdentifierNotFound(sprintf(
                'To use the group identifier, you must set $groupId when instantiating %s',
                self::class,
            ));
        }

        return $this->groupId;
    }

    /**
     * @throws DceIdentifierNotFound if an org identifier was not provided upon instantiation.
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
     * @throws DceIdentifierNotFound if a user identifier was not provided upon instantiation.
     */
    public function userId(): int
    {
        if ($this->userId === null) {
            throw new DceIdentifierNotFound(sprintf(
                'To use the user identifier, you must set $userId when instantiating %s',
                self::class,
            ));
        }

        return $this->userId;
    }
}
