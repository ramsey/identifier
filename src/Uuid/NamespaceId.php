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

/**
 * Version 3 and 5 UUIDs use name space IDs to ensure the uniqueness of name-based identifiers within a unique name space.
 *
 * RFC 9562 defines name space IDs for names created for the domain name system (DNS), uniform resource locators (URLs),
 * ISO object IDs (OIDs) and X.500 distinguished names. This list may be expanded in the future, and it's not intended
 * to limit other, specialized name space IDs for different applications.
 *
 * @link https://www.rfc-editor.org/rfc/rfc9562#section-6.6 RFC 9562, section 6.6. Namespace ID Usage and Allocation.
 * @link https://www.iana.org/assignments/uuid/uuid.xhtml#uuid-namespace-ids IANA UUID Namespace IDs registry.
 */
enum NamespaceId: string
{
    /**
     * Names in the CBOR PEN namespace represent Concise Binary Object Representation Private Enterprise Numbers (CBOR PEN).
     *
     * @link https://www.ietf.org/archive/id/draft-ietf-suit-manifest-34.html#section-8.4.8.1 draft-ietf-suit-manifest-34, section 8.4.8.1. CBOR PEN UUID Namespace Identifier.
     */
    case CborPen = '47fbdabb-f2e4-55f0-bb39-3620c2f6df4e';

    /**
     * Names in the DNS namespace represent fully qualified domain names.
     *
     * @link https://www.rfc-editor.org/rfc/rfc9499 RFC 9499, DNS Terminology.
     */
    case Dns = '6ba7b810-9dad-11d1-80b4-00c04fd430c8';

    /**
     * Names in the OID namespace represent ISO object identifiers (OID).
     *
     * @link https://en.wikipedia.org/wiki/Object_identifier Object identifier.
     * @link https://www.itu.int/itu-t/recommendations/rec.aspx?rec=X.660/ ISO/IEC 9834-1, ITU-T Rec. X.660, 2011.
     */
    case Oid = '6ba7b812-9dad-11d1-80b4-00c04fd430c8';

    /**
     * Names in the URL namespace represent uniform resource locators (URL).
     *
     * @link https://www.rfc-editor.org/rfc/rfc3986 RFC 3986, Uniform Resource Identifier (URI): Generic Syntax.
     */
    case Url = '6ba7b811-9dad-11d1-80b4-00c04fd430c8';

    /**
     * Names in the X500 namespace represent the Distinguished Names (DN) of entries in an X.500 directory service.
     *
     * @link https://en.wikipedia.org/wiki/X.500 X.500.
     * @link https://en.wikipedia.org/wiki/Distinguished_Name Distinguished Name
     * @link https://www.itu.int/ITU-T/recommendations/rec.aspx?rec=x.500 ISO/IEC 9594-1, ITU-T Rec. X.500, 2019.
     */
    case X500 = '6ba7b814-9dad-11d1-80b4-00c04fd430c8';

    /**
     * Returns a {@see \Ramsey\Identifier\Uuid} instance of the namespace identifier.
     */
    public function uuid(): Uuid
    {
        return match ($this) {
            self::Dns, self::Oid, self::Url, self::X500 => new UuidV1($this->value),
            self::CborPen => new UuidV5($this->value),
        };
    }
}
