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

namespace Ramsey\Identifier\Service\Node;

use Ramsey\Identifier\Exception\NodeNotFound;

/**
 * A node service that retrieves a node by stepping through a list of node
 * services until it obtains a node ID
 */
final class FallbackNodeService implements NodeService
{
    /**
     * @param iterable<NodeService> $providers List of node services
     */
    public function __construct(private readonly iterable $providers)
    {
    }

    /**
     * @throws NodeNotFound
     */
    public function getNode(): string
    {
        $lastProviderException = null;

        foreach ($this->providers as $provider) {
            try {
                return $provider->getNode();
            } catch (NodeNotFound $exception) {
                $lastProviderException = $exception;

                continue;
            }
        }

        throw new NodeNotFound(
            'Unable to find a suitable node service',
            0,
            $lastProviderException,
        );
    }
}
