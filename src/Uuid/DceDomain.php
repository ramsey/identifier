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

/**
 * DCE local domains for version 2, DCE Security UUIDs.
 *
 * @link https://pubs.opengroup.org/onlinepubs/9696989899/chap11.htm#tagcjh_14_05_01_01 DCE 1.1: Auth & Sec, §11.5.1.1.
 */
enum DceDomain: int
{
    /**
     * Principal domain.
     */
    case Person = 0;

    /**
     * Group domain.
     */
    case Group = 1;

    /**
     * Organization domain.
     */
    case Org = 2;

    /**
     * Returns the "string name" value, as defined by DCE.
     */
    public function dceStringName(): string
    {
        return match ($this) {
            DceDomain::Person => 'person',
            DceDomain::Group => 'group',
            DceDomain::Org => 'org',
        };
    }
}
