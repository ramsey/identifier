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

namespace Ramsey\Identifier\Service\Nic;

use Ramsey\Identifier\Exception\MacAddressNotFound;

/**
 * A NIC that attempts to retrieve a MAC address by stepping through a list of
 * NIC providers until obtaining a MAC address
 */
final class FallbackNic implements Nic
{
    /**
     * @param iterable<Nic> $providers List of NIC services
     */
    public function __construct(private readonly iterable $providers)
    {
    }

    /**
     * @throws MacAddressNotFound
     */
    public function address(): string
    {
        $lastProviderException = null;

        foreach ($this->providers as $provider) {
            try {
                return $provider->address();
            } catch (MacAddressNotFound $exception) {
                $lastProviderException = $exception;

                continue;
            }
        }

        throw new MacAddressNotFound('Unable to find a MAC address', 0, $lastProviderException);
    }
}
