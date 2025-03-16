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

namespace Ramsey\Identifier\Service\Clock;

use DateTimeInterface;
use Ramsey\Identifier\Exception\InvalidArgument;

use function random_int;

use const PHP_INT_MAX;

/**
 * Maintains the state of the node and date-time values to calculate the next
 * clock sequence value
 */
final class StatefulSequence implements Sequence
{
    /**
     * The current value of this sequence.
     *
     * @var int<0, max>
     */
    private static int $sequence = 0;

    /**
     * The current state of the sequence is a kind of cache key. As long as it
     * doesn't change, we will increment the sequence value. When this changes,
     * the sequence value starts over at zero.
     */
    private static ?string $stateKey = null;

    /**
     * @param int<0, max> | null $initialSequence An initial clock sequence
     *     value; if no value is provided, one will be randomly generated
     * @param int | non-empty-string | null $initialNode An initial node value
     *     identifying the machine
     * @param DateTimeInterface | null $initialDateTime An initial date-time
     *     value to prevent clock sequence collisions if the time runs backwards
     * @param Precision $precision Whether to compare the date-time
     *     using microseconds or milliseconds
     */
    public function __construct(
        ?int $initialSequence = null,
        int | string | null $initialNode = null,
        ?DateTimeInterface $initialDateTime = null,
        private readonly Precision $precision = Precision::Microsecond,
    ) {
        if ($initialNode !== null && $initialDateTime === null) {
            throw new InvalidArgument('When specifying an initial node, you must also specify an initial date-time');
        }

        if ($initialNode === null && $initialDateTime !== null) {
            throw new InvalidArgument('When specifying an initial date-time, you must also specify an initial node');
        }

        $this->setSequence($this->buildStateKey($initialNode, $initialDateTime), $initialSequence);
    }

    public function value(int | string $node, DateTimeInterface $dateTime): int
    {
        $stateKey = $this->buildStateKey($node, $dateTime);

        // If the state key is null, then we can proceed to increment the sequence
        // value because it should be the initial sequence set when instantiating
        // this class. However, if the state key is not null, and it has changed,
        // we need to reset the sequence.
        if (self::$stateKey !== null && self::$stateKey !== $stateKey) {
            $this->setSequence($stateKey, null);
        }

        // While the state is the same, increment the sequence.
        if (self::$sequence === PHP_INT_MAX) {
            // Roll over the sequence.
            self::$sequence = 0;
        } else {
            self::$sequence++;
        }

        if (self::$stateKey === null) {
            self::$stateKey = $stateKey;
        }

        return self::$sequence;
    }

    /**
     * @param int<0, max> | null $sequence
     */
    private function setSequence(?string $stateKey, ?int $sequence): void
    {
        self::$stateKey = $stateKey;
        self::$sequence = $sequence ?? random_int(0, PHP_INT_MAX);
    }

    private function buildStateKey(int | string | null $node, ?DateTimeInterface $dateTime): ?string
    {
        if ($node === null || $dateTime === null) {
            return null;
        }

        return $node . '_' . $dateTime->format($this->precision->value);
    }
}
