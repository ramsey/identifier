<?php

/**
 * This file is part of ramsey/identifier
 *
 * ramsey/identifier is open source software: you can distribute
 * it and/or modify it under the terms of the MIT License
 * (the "License"). You may not use this file except in
 * compliance with the License.
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or
 * implied. See the License for the specific language governing
 * permissions and limitations under the License.
 *
 * @copyright Copyright (c) Ben Ramsey <ben@benramsey.com>
 * @license https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Ramsey\Identifier\Uuid;

use BadMethodCallException;
use Identifier\UuidInterface;
use Ramsey\Identifier\Uuid;

/**
 * The Max UUID is a special form of UUID that is specified to have all 128
 * bits set to one (1)
 *
 * @link https://datatracker.ietf.org/doc/html/draft-peabody-dispatch-new-uuid-format-04#section-5.4 New UUID Formats, § 5.4
 *
 * @psalm-immutable
 */
final class MaxUuid implements UuidInterface
{
    use StandardUuid;

    public function __construct()
    {
        $this->uuid = Uuid::MAX;
    }

    /**
     * @inheritDoc
     */
    public function __unserialize(array $data): void
    {
        $this->uuid = Uuid::MAX;
    }

    public function getVariant(): never
    {
        throw new BadMethodCallException('Max UUIDs do not have a variant field');
    }

    public function getVersion(): never
    {
        throw new BadMethodCallException('Max UUIDs do not have a version field');
    }

    /**
     * @codeCoverageIgnore This code path is unreachable.
     */
    protected function getValidationPattern(): never
    {
        throw new BadMethodCallException('Max UUIDs do not have a validation pattern');
    }
}
