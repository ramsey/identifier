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
 * The version number describes how the UUID was generated
 *
 * | **Msb0** | **Msb1** | **Msb2** | **Msb3** | **Version** | **Description**                                             |
 * | :------: | :------: | :------: | :------: | :---------: | :---------------------------------------------------------- |
 * |     0    |    0     |    0     |    0     |      0      | Unused                                                      |
 * |     0    |    0     |    0     |    1     |      1      | The Gregorian time-based UUID                               |
 * |     0    |    0     |    1     |    0     |      2      | Reserved for DCE Security version                           |
 * |     0    |    0     |    1     |    1     |      3      | The name-based version that uses MD5 hashing                |
 * |     0    |    1     |    0     |    0     |      4      | The randomly or pseudo-randomly generated version           |
 * |     0    |    1     |    0     |    1     |      5      | The name-based version that uses SHA-1 hashing              |
 * |     0    |    1     |    1     |    0     |      6      | Reordered Gregorian time-based UUID                         |
 * |     0    |    1     |    1     |    1     |      7      | Unix Epoch time-based UUID                                  |
 * |     1    |    0     |    0     |    0     |      8      | Reserved for custom UUID formats                            |
 * |     1    |    0     |    0     |    1     |      9      | Reserved for future definition                              |
 * |     1    |    0     |    1     |    0     |     10      | Reserved for future definition                              |
 * |     1    |    0     |    1     |    1     |     11      | Reserved for future definition                              |
 * |     1    |    1     |    0     |    0     |     12      | Reserved for future definition                              |
 * |     1    |    1     |    0     |    1     |     13      | Reserved for future definition                              |
 * |     1    |    1     |    1     |    0     |     14      | Reserved for future definition                              |
 * |     1    |    1     |    1     |    1     |     15      | Reserved for future definition                              |
 *
 * @link https://www.rfc-editor.org/rfc/rfc9562#section-4.2 RFC 9562, section 4.2. Verson Field
 */
enum Version: int
{
    /**
     * Gregorian time-based UUID
     */
    case GregorianTime = 1;

    /**
     * DCE Security version UUID
     */
    case DceSecurity = 2;

    /**
     * Name-based UUID that uses MD5 hashing
     */
    case HashMd5 = 3;

    /**
     * Randomly or pseudo-randomly generated UUID
     */
    case Random = 4;

    /**
     * Name-based UUID that uses SHA-1 hashing
     */
    case HashSha1 = 5;

    /**
     * Reordered Gregorian time-based UUID
     */
    case ReorderedGregorianTime = 6;

    /**
     * Unix Epoch time-based UUID
     */
    case UnixTime = 7;

    /**
     * Reserved for custom UUID formats
     */
    case Custom = 8;

    /**
     * Alias for {@see self::GregorianTime}
     */
    public const V1 = self::GregorianTime;

    /**
     * Alias for {@see self::DceSecurity}
     */
    public const V2 = self::DceSecurity;

    /**
     * Alias for {@see self::HashMd5}
     */
    public const V3 = self::HashMd5;

    /**
     * Alias for {@see self::Random}
     */
    public const V4 = self::Random;

    /**
     * Alias for {@see self::HashSha1}
     */
    public const V5 = self::HashSha1;

    /**
     * Alias for {@see self::ReorderedGregorianTime}
     */
    public const V6 = self::ReorderedGregorianTime;

    /**
     * Alias for {@see self::UnixTime}
     */
    public const V7 = self::UnixTime;

    /**
     * Alias for {@see self::Custom}
     */
    public const V8 = self::Custom;
}
