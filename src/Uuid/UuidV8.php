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

namespace Ramsey\Identifier\Uuid;

use Ramsey\Identifier\Uuid;
use Ramsey\Identifier\Uuid\Internal\Standard;

/**
 * Version 8, custom format UUIDs provide an RFC 9562 compatible format for experimental or vendor-specific uses.
 *
 * The only requirement for version 8 UUIDs is that the version and variant bits must be set. Otherwise, implementations
 * are free to set the other bits according to their needs. As a result, the uniqueness of version 8 UUIDs is
 * implementation-specific and should not be assumed.
 *
 * @link https://www.rfc-editor.org/rfc/rfc9562#section-5.8 RFC 9562, section 5.8. UUID Version 8.
 */
final readonly class UuidV8 implements Uuid
{
    use Standard;

    public function getVersion(): Version
    {
        return Version::Custom;
    }
}
