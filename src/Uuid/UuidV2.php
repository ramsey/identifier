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

use Ramsey\Identifier\NodeBasedUuid;
use Ramsey\Identifier\TimeBasedUuid;
use Ramsey\Identifier\Uuid\Utility\Format;
use Ramsey\Identifier\Uuid\Utility\NodeBased;
use Ramsey\Identifier\Uuid\Utility\Standard;
use Ramsey\Identifier\Uuid\Utility\TimeBased;

use function hexdec;
use function substr;

/**
 * DCE Security version, or version 2, UUIDs include local domain identifier,
 * local ID for the specified domain, and node values that are combined into a
 * 128-bit unsigned integer
 *
 * It is important to note that a version 2 UUID suffers from some loss of
 * fidelity of the timestamp, due to replacing the time_low field with the
 * local identifier. When constructing the timestamp value for date
 * purposes, we replace the local identifier bits with zeros. As a result,
 * the timestamp can be off by a range of 0 to 429.4967295 seconds (or 7
 * minutes, 9 seconds, and 496730 microseconds).
 *
 * Astute observers might note this value directly corresponds to 2^32 - 1,
 * or 0xffffffff. The local identifier is 32-bits, and we have set each of
 * these bits to 0, so the maximum range of timestamp drift is 0x00000000
 * to 0xffffffff (counted in 100-nanosecond intervals).
 *
 * @link https://www.rfc-editor.org/rfc/rfc9562#section-5.2 RFC 9562, section 5.2. UUID Version 2
 * @link https://publications.opengroup.org/c311 DCE 1.1: Authentication and Security Services
 * @link https://publications.opengroup.org/c706 DCE 1.1: Remote Procedure Call
 * @link https://pubs.opengroup.org/onlinepubs/9696989899/chap5.htm#tagcjh_08_02_01_01 DCE 1.1: Auth & Sec, §5.2.1.1
 * @link https://pubs.opengroup.org/onlinepubs/9696989899/chap11.htm#tagcjh_14_05_01_01 DCE 1.1: Auth & Sec, §11.5.1.1
 * @link https://pubs.opengroup.org/onlinepubs/9629399/apdxa.htm DCE 1.1: RPC, Appendix A
 * @link https://github.com/google/uuid Go package for UUIDs (includes DCE implementation)
 */
final readonly class UuidV2 implements NodeBasedUuid, TimeBasedUuid
{
    use Standard {
        isValid as private baseIsValid;
    }
    use NodeBased;
    use TimeBased;

    /**
     * Returns the local domain to which the local identifier belongs
     *
     * For example, if the local domain is {@see DceDomain::Person}, then the
     * local identifier should indicate the ID of a person's account on the
     * local host. On POSIX systems, this is usually the UID.
     */
    public function getLocalDomain(): DceDomain
    {
        /** @var DceDomain */
        return $this->getLocalDomainFromUuid($this->uuid, $this->format);
    }

    /**
     * Returns an identifier meaningful to the local host where this UUID was
     * created
     *
     * The type of this identifier is indicated by the domain returned from
     * {@see self::getLocalDomain()}. For example, if the domain is
     * {@see DceDomain::Group}, this identifier is a group ID on the local
     * host. On POSIX systems, this is usually the GID.
     */
    public function getLocalIdentifier(): int
    {
        return (int) hexdec(substr($this->getFormat(Format::String), 0, 8));
    }

    public function getVersion(): Version
    {
        return Version::DceSecurity;
    }

    protected function isValid(string $uuid, ?Format $format): bool
    {
        return $this->baseIsValid($uuid, $format)
            && $this->getLocalDomainFromUuid($uuid, $format) !== null;
    }
}
