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

use DateTimeImmutable;
use DateTimeInterface;

use function random_int;

use const PHP_INT_MAX;

/**
 * Maintains the state of the node and date-time values to calculate the next
 * clock sequence value
 */
final class StatefulSequence implements Sequence
{
    /**
     * @var int<0, max>
     */
    private int $clockSeq;

    private int | string | null $lastNode;
    private DateTimeInterface $lastTime;

    /**
     * @param int<0, max> | null $initialClockSeq An initial clock sequence
     *     value; if no value is provided, one will be randomly generated
     * @param int | non-empty-string | null $initialNode An initial node value
     *     identifying the machine
     * @param DateTimeInterface | null $initialDateTime An initial date-time
     *     value to prevent clock sequence collisions if the time runs backwards
     * @param Precision $precision Whether to compare the date-time
     *     using microseconds or milliseconds
     */
    public function __construct(
        ?int $initialClockSeq = null,
        int | string | null $initialNode = null,
        ?DateTimeInterface $initialDateTime = null,
        private readonly Precision $precision = Precision::Microsecond,
    ) {
        $this->clockSeq = $initialClockSeq ?? random_int(0, PHP_INT_MAX);
        $this->lastNode = $initialNode;
        $this->lastTime = $initialDateTime ?? new DateTimeImmutable();
    }

    public function value(int | string $node, DateTimeInterface $dateTime): int
    {
        if ($this->lastNode !== null && $node !== $this->lastNode) {
            // If the node has changed, regenerate the clock sequence.
            $this->clockSeq = random_int(0, PHP_INT_MAX);
        }

        if ($dateTime->format($this->precision->value) <= $this->lastTime->format($this->precision->value)) {
            if ($this->clockSeq === PHP_INT_MAX) {
                // Roll over the clock sequence.
                $this->clockSeq = 0;
            } else {
                $this->clockSeq++;
            }
        }

        $this->lastNode = $node;
        $this->lastTime = clone $dateTime;

        return $this->clockSeq;
    }
}
