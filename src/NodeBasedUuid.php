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
 * Describes the interface of a UUID that includes a node identifier
 * (or MAC address)
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
     * @link https://www.rfc-editor.org/rfc/rfc4122.html#section-4.1.6 RFC 4122: Node
     * @link https://www.rfc-editor.org/rfc/rfc4122.html#section-4.5 RFC 4122: Node IDs that Do Not Identify the Host
     * @link https://www.ietf.org/archive/id/draft-ietf-uuidrev-rfc4122bis-00.html#section-5.1 rfc4122bis: UUID Version 1
     * @link https://www.ietf.org/archive/id/draft-ietf-uuidrev-rfc4122bis-00.html#section-5.6 rfc4122bis: UUID Version 6
     * @link https://www.ietf.org/archive/id/draft-ietf-uuidrev-rfc4122bis-00.html#section-6.9 rfc4122bis: UUIDs that Do Not Identify the Host
     *
     * @return non-empty-string
     */
    public function getNode(): string;
}
