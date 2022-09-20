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

namespace Ramsey\Identifier\Uuid\Dce;

/**
 * DCE local domains for version 2, DCE Security UUIDs
 *
 * @link https://pubs.opengroup.org/onlinepubs/9696989899/chap11.htm#tagcjh_14_05_01_01 DCE 1.1: Auth & Sec, §11.5.1.1
 */
enum Domain: int
{
    /**
     * Principal domain
     */
    case Person = 0;

    /**
     * Group domain
     */
    case Group = 1;

    /**
     * Organization domain
     */
    case Org = 2;
}
