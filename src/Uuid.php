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

namespace Ramsey\Identifier;

use Identifier\BytesIdentifier;
use Identifier\IntegerIdentifier;
use Ramsey\Identifier\Uuid\Variant;
use Ramsey\Identifier\Uuid\Version;

/**
 * A universally unique identifier (UUID).
 *
 * @link https://www.rfc-editor.org/rfc/rfc9562 RFC 9562: Universally Unique IDentifiers (UUIDs).
 */
interface Uuid extends BytesIdentifier, IntegerIdentifier
{
    /**
     * Returns the variant of this UUID, describing the layout of the UUID.
     *
     * @link https://www.rfc-editor.org/rfc/rfc9562#section-4.1 RFC 9562, section 4.1. Variant Field.
     */
    public function getVariant(): Variant;

    /**
     * Returns the version of this UUID, describing how the UUID was generated.
     *
     * @link https://www.rfc-editor.org/rfc/rfc9562#section-4.2 RFC 9562, section 4.2. Version Field.
     */
    public function getVersion(): Version;

    /**
     * Returns a string representation of the UUID encoded as hexadecimal digits.
     *
     * @return non-empty-string
     */
    public function toHexadecimal(): string;

    /**
     * Returns the standard string representation of the UUID as a URN.
     *
     * @link https://www.rfc-editor.org/rfc/rfc9562#figure-4 RFC 9562, Figure 4: Example URN Namespace for UUID.
     * @link https://www.rfc-editor.org/rfc/rfc8141 RFC 8141: Uniform Resource Names (URNs).
     *
     * @return non-empty-string
     */
    public function toUrn(): string;
}
