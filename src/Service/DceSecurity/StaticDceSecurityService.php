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

use Ramsey\Identifier\Exception\DceSecurityException;

use function sprintf;

/**
 * A service that provides static person, group, and organization IDs
 */
final class StaticDceSecurityService implements DceSecurityServiceInterface
{
    /**
     * Constructs a static DCE Security service
     *
     * Each parameter is optional, so you may only provide the one you intend
     * to use. However, when attempting to use a method for a property not
     * provided, this class will throw a {@see DceSecurityException}.
     *
     * @param int<0, max> | null $personId An optional person identifier, or UID
     * @param int<0, max> | null $groupId An optional group identifier, or GID
     * @param int<0, max> | null $orgId An optional organization identifier
     */
    public function __construct(
        private readonly ?int $personId = null,
        private readonly ?int $groupId = null,
        private readonly ?int $orgId = null,
    ) {
    }

    /**
     * @throws DceSecurityException if a group identifier was not provided upon instantiation
     */
    public function getGroupIdentifier(): int
    {
        if ($this->groupId === null) {
            throw new DceSecurityException(sprintf(
                'To use the group identifier, you must set $groupId when instantiating %s',
                self::class,
            ));
        }

        return $this->groupId;
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
     * @throws DceSecurityException if a person identifier was not provided upon instantiation
     */
    public function getPersonIdentifier(): int
    {
        if ($this->personId === null) {
            throw new DceSecurityException(sprintf(
                'To use the person identifier, you must set $personId when instantiating %s',
                self::class,
            ));
        }

        return $this->personId;
    }
}
