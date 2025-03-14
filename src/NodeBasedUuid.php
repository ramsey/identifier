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

/**
 * A UUID that includes a node identifier (or MAC address)
 */
interface NodeBasedUuid extends Uuid
{
    /**
     * Returns a string representation of the node (usually the host MAC
     * address), encoded as hexadecimal characters
     *
     * If the node has the multicast bit set, this indicates it was randomly
     * generated, rather than identifying a host machine.
     *
     * @link https://www.rfc-editor.org/rfc/rfc9562#section-5.1 RFC 9562, section 5.1. UUID Version 1
     * @link https://www.rfc-editor.org/rfc/rfc9562#section-5.6 RFC 9562, section 5.6. UUID Version 6
     * @link https://www.rfc-editor.org/rfc/rfc9562#section-6.10 RFC 9562, section 6.10. UUIDs That Do Not Identify the Host
     *
     * @return non-empty-string
     */
    public function getNode(): string;
}
