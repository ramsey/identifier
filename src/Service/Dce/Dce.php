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
use Ramsey\Identifier\Uuid\UuidV2;

/**
 * Provides system group, user, and organization identifiers for use with Distributed Computing Environment (DCE)
 * specifications.
 *
 * @link https://publications.opengroup.org/c311 DCE 1.1: Authentication and Security Services.
 * @see UuidV2
 */
interface Dce
{
    /**
     * Returns a group identifier (i.e., GID) for the system.
     *
     * @link https://en.wikipedia.org/wiki/Group_identifier Group identifier.
     *
     * @return int<0, 4294967295> 32-bit group identifier
     *
     * @throws DceIdentifierNotFound when unable to find a group identifier.
     */
    public function groupId(): int;

    /**
     * Returns an organization identifier.
     *
     * @return int<0, 4294967295> 32-bit org identifier
     *
     * @throws DceIdentifierNotFound when unable to find an organization identifier.
     */
    public function orgId(): int;

    /**
     * Returns a user identifier (i.e., UID) for the system.
     *
     * @link https://en.wikipedia.org/wiki/User_identifier User identifier.
     *
     * @return int<0, 4294967295> 32-bit user identifier
     *
     * @throws DceIdentifierNotFound when unable to find a user identifier.
     */
    public function userId(): int;
}
