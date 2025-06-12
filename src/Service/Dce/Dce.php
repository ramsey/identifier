<?php

/**
 * This file is part of ramsey/identifier
 *
 * ramsey/identifier is open source software: you can distribute it and/or modify it under the terms of the MIT License
 * (the "License"). You may not use this file except in compliance with the License.
 *
 * @copyright Copyright (c) Ben Ramsey <ben@benramsey.com>
 * @license https://opensource.org/licenses/MIT MIT License
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
