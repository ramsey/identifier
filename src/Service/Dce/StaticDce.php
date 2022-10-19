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

use function sprintf;

/**
 * Provides pre-determined user, group, and organization IDs for generating
 * DCE Security (version 2) UUIDs
 */
final class StaticDce implements Dce
{
    /**
     * Constructs a static DCE service
     *
     * Each parameter is optional, so you may only provide the one you intend
     * to use. However, when attempting to use a method for a property not
     * provided, this class will throw a {@see DceIdentifierNotFound}.
     *
     * @param int<0, max> | null $userId An optional user identifier, or UID
     * @param int<0, max> | null $groupId An optional group identifier, or GID
     * @param int<0, max> | null $orgId An optional organization identifier
     */
    public function __construct(
        private readonly ?int $userId = null,
        private readonly ?int $groupId = null,
        private readonly ?int $orgId = null,
    ) {
    }

    /**
     * @throws DceIdentifierNotFound if a group identifier was not provided upon instantiation
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
     * @throws DceIdentifierNotFound if a user identifier was not provided upon instantiation
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
