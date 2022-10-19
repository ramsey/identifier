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

use Ramsey\Identifier\Exception\DceIdentifierNotFound;
use Ramsey\Identifier\Uuid\UuidV2;

/**
 * Defines a Distributed Computing Environment (DCE) interface for getting
 * system group, user, and organization identifiers
 *
 * @see UuidV2
 */
interface Dce
{
    /**
     * Returns a group identifier for the system
     *
     * @link https://en.wikipedia.org/wiki/Group_identifier Group identifier
     *
     * @return int<0, max>
     *
     * @throws DceIdentifierNotFound when unable to find a group identifier
     */
    public function groupId(): int;

    /**
     * Returns an organization identifier
     *
     * @return int<0, max>
     *
     * @throws DceIdentifierNotFound when unable to find an organization identifier
     */
    public function orgId(): int;

    /**
     * Returns a user identifier for the system
     *
     * @link https://en.wikipedia.org/wiki/User_identifier User identifier
     *
     * @return int<0, max>
     *
     * @throws DceIdentifierNotFound when unable to find a user identifier
     */
    public function userId(): int;
}
