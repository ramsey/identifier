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

namespace Ramsey\Identifier\Uuid;

use Override;
use Ramsey\Identifier\Uuid;
use Ramsey\Identifier\Uuid\Internal\Standard;

/**
 * Version 5 UUIDs are named-based, using a combination of a namespace and name
 * that are hashed into a 128-bit unsigned integer using the SHA-1 hashing
 * algorithm
 *
 * @link https://www.rfc-editor.org/rfc/rfc9562#section-5.5 RFC 9562, section 5.5. UUID Version 5
 */
final readonly class UuidV5 implements Uuid
{
    use Standard;

    #[Override]
    public function getVersion(): Version
    {
        return Version::NameSha1;
    }
}
