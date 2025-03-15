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
 * | **Msb0** | **Msb1** | **Msb2** | **Msb3** | **Variant** | **Description**                                             |
 * | :------: | :------: | :------: | :------: | :---------: | :---------------------------------------------------------- |
 * |     0    |    x     |    x     |    x     |     1-7     | Reserved, NCS backward compatibility, and includes Nil UUID |
 * |     1    |    0     |    x     |    x     |  8-9, A-B   | The variant specified in this document                      |
 * |     1    |    1     |    0     |    x     |     C-D     | Reserved, Microsoft Corporation backward compatibility      |
 * |     1    |    1     |    1     |    x     |     E-F     | Reserved for future definition, and includes Max UUID       |
 *
 * In reading this table, we find that, if the first 3 bits of the variant field
 * are all 1s (i.e., the decimal value 7), then the variant is reserved for
 * future definition. If the first three bits are two 1s followed by a 0 (i.e.,
 * the decimal value 6), then the variant is reserved for Microsoft
 * Corporation. If the first two bits are a 1 and 0 (i.e., the decimal value
 * 2), then the variant is for RFC 9562. Finally, if the first bit is 0,
 * then it's reserved for NCS, for backward compatibility.
 *
 * @link https://www.rfc-editor.org/rfc/rfc9562#section-4.1 RFC 9562, section 4.1. Variant Field
 */
enum Variant: int
{
    /**
     * Reserved. Network Computing System (NCS) backward compatibility, and includes Nil UUID as per
     * {@link https://www.rfc-editor.org/rfc/rfc9562#section-5.9 RFC 9562, section 5.9}.
     */
    case Ncs = 0b0;

    /**
     * The variant specified in {@link https://www.rfc-editor.org/rfc/rfc9562 RFC 9562}.
     */
    case Rfc9562 = 0b10;

    /**
     * Reserved. Microsoft Corporation backward compatibility.
     */
    case Microsoft = 0b110;

    /**
     * Reserved for future definition and includes Max UUID as per
     * {@link https://www.rfc-editor.org/rfc/rfc9562#section-5.10 RFC 9562, section 5.10}.
     */
    case Future = 0b111;

    /**
     * Alias for {@see self::Rfc9562}
     */
    public const Rfc4122 = self::Rfc9562; // phpcs:ignore
}
