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

namespace Ramsey\Identifier;

use Identifier\BinaryIdentifier;
use Identifier\IntegerIdentifier;
use Ramsey\Identifier\Uuid\Variant;
use Ramsey\Identifier\Uuid\Version;

/**
 * Describes the interface of a universally unique identifier (UUID)
 *
 * @link https://www.rfc-editor.org/rfc/rfc4122.html RFC 4122
 */
interface Uuid extends BinaryIdentifier, IntegerIdentifier
{
    /**
     * Returns the variant of this UUID, describing the layout of the UUID
     *
     * @link https://www.rfc-editor.org/rfc/rfc4122.html#section-4.1.1 RFC 4122: Variant
     * @link https://www.ietf.org/archive/id/draft-ietf-uuidrev-rfc4122bis-00.html#section-4.1 rfc4122bis: Variant Field
     */
    public function getVariant(): Variant;

    /**
     * Returns the version of this UUID, describing how the UUID was generated
     *
     * @link https://www.rfc-editor.org/rfc/rfc4122.html#section-4.1.3 RFC 4122: Version
     * @link https://www.ietf.org/archive/id/draft-ietf-uuidrev-rfc4122bis-00.html#section-4.2 rfc4122bis: Version Field
     */
    public function getVersion(): Version;

    /**
     * Returns a string representation of the UUID encoded as hexadecimal digits
     */
    public function toHexadecimal(): string;

    /**
     * Returns the string standard representation of the UUID as a URN
     *
     * @link http://en.wikipedia.org/wiki/Uniform_Resource_Name Uniform Resource Name
     * @link https://www.rfc-editor.org/rfc/rfc4122.html#section-3 RFC 4122: Namespace Registration Template
     * @link https://www.ietf.org/archive/id/draft-ietf-uuidrev-rfc4122bis-00.html#appendix-A rfc4122bis: Namespace Registration Template
     *
     * @return non-empty-string
     */
    public function toUrn(): string;
}
