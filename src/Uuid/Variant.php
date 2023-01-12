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

/**
 * The variant number describes the layout of the UUID
 *
 *     Msb0  Msb1  Msb2  Description
 *      0     x     x    Reserved, NCS backward compatibility.
 *      1     0     x    The variant specified in this document.
 *      1     1     0    Reserved, Microsoft Corporation backward
 *                       compatibility
 *      1     1     1    Reserved for future definition.
 *
 * In reading this table, we find that, if the first 3 bits of the variant field
 * are all 1s (i.e., the decimal value 7), then the variant is reserved for
 * future definition. If the first three bits are two 1s followed by a 0 (i.e.,
 * the decimal value 6), then the variant is reserved for Microsoft
 * Corporation. If the first two bits are a 1 and 0 (i.e., the decimal value
 * 2), then the variant is for RFC 4122. Finally, if the first bit is 0,
 * then it's reserved for NCS, for backward compatibility.
 *
 * @link https://www.rfc-editor.org/rfc/rfc4122.html#section-4.1.1 RFC 4122: Variant
 * @link https://www.ietf.org/archive/id/draft-ietf-uuidrev-rfc4122bis-00.html#section-4.1 rfc4122bis: Variant Field
 */
enum Variant: int
{
    /**
     * Reserved, NCS backward compatibility
     */
    case ReservedNcs = 0;

    /**
     * The UUID layout specified in RFC 4122
     */
    case Rfc4122 = 2;

    /**
     * Reserved, Microsoft Corporation backward compatibility
     */
    case ReservedMicrosoft = 6;

    /**
     * Reserved for future definition
     */
    case ReservedFuture = 7;
}
