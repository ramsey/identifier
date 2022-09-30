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

namespace Ramsey\Identifier\Uuid;

use Identifier\Uuid\UuidInterface;

/**
 * Version 3 and 5 UUIDs use name space IDs to ensure uniqueness of name-based
 * identifiers within a unique name space
 *
 * RFC 4122 defines name space IDs for names created for the domain name system
 * (DNS), uniform resource locators (URLs), ISO object IDs (OIDs) and X.500
 * distinguished names. This list may be expanded in the future, and it's not
 * intended to limit other, specialized name space IDs for different
 * applications.
 */
enum NamespaceId: string
{
    /**
     * Name string is a fully-qualified domain name
     */
    case DNS = '6ba7b810-9dad-11d1-80b4-00c04fd430c8';

    /**
     * Name string is an ISO OID
     */
    case OID = '6ba7b812-9dad-11d1-80b4-00c04fd430c8';

    /**
     * Name string is a URL
     */
    case URL = '6ba7b811-9dad-11d1-80b4-00c04fd430c8';

    /**
     * Name string is an X.500 DN (in DER or a text output format)
     */
    case X500 = '6ba7b814-9dad-11d1-80b4-00c04fd430c8';

    /**
     * Returns a UUID instance of the namespace
     */
    public function uuid(): UuidInterface
    {
        return new UuidV1($this->value);
    }
}
