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
     * @link https://datatracker.ietf.org/doc/html/rfc4122#section-4.1.6 RFC 4122: Node
     * @link https://datatracker.ietf.org/doc/html/rfc4122#section-4.5 RFC 4122: Node IDs that Do Not Identify the Host
     *
     * @return non-empty-string
     */
    public function getNode(): string;
}
