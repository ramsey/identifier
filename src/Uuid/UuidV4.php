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

namespace Ramsey\Identifier\Uuid;

use Ramsey\Identifier\Uuid;
use Ramsey\Identifier\Uuid\Internal\Standard;

/**
 * Random, or version 4, UUIDs are randomly generated 128-bit integers.
 *
 * @link https://www.rfc-editor.org/rfc/rfc9562#section-5.4 RFC 9562, section 5.4. UUID Version 4.
 */
final readonly class UuidV4 implements Uuid
{
    use Standard;

    public function getVersion(): Version
    {
        return Version::Random;
    }
}
