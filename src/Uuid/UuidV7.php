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
use Ramsey\Identifier\TimeBasedUuid;
use Ramsey\Identifier\Uuid\Internal\Standard;
use Ramsey\Identifier\Uuid\Internal\TimeBased;

/**
 * Unix Epoch time, or version 7, UUIDs include a timestamp in milliseconds
 * since the Unix Epoch
 *
 * Version 7 UUIDs are designed to be monotonically increasing and sortable.
 *
 * @link https://www.rfc-editor.org/rfc/rfc9562#section-5.7 RFC 9562, section 5.7. UUID Version 7
 */
final readonly class UuidV7 implements TimeBasedUuid
{
    use Standard;
    use TimeBased;

    #[Override]
    public function getVersion(): Version
    {
        return Version::UnixTime;
    }
}
