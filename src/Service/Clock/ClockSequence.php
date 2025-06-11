<?php

/**
 * This file is part of ramsey/identifier
 *
 * ramsey/identifier is open source software: you can distribute it and/or modify it under the terms of the MIT License
 * (the "License"). You may not use this file except in compliance with the License.
 *
 * @copyright Copyright (c) Ben Ramsey <ben@benramsey.com>
 * @license https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Ramsey\Identifier\Service\Clock;

use DateTimeInterface;
use Ramsey\Identifier\Service\Sequence\Sequence;
use Ramsey\Identifier\Service\Sequence\SequenceOverflow;

/**
 * Derives a clock sequence value, to avoid duplicates or collisions.
 *
 * From RFC 9562, section 5.1.:
 *
 * > UUIDv1 also features a clock sequence field that is used to help avoid duplicates that could arise when the clock
 * > is set backwards in time or if the Node ID changes.
 * >
 * > [...]
 * >
 * > If the clock is set backwards, or if it might have been set backwards (e.g., while the system was powered off), and
 * > the UUID generator cannot be sure that no UUIDs were generated with timestamps larger than the value to which the
 * > clock was set, then the clock sequence MUST be changed. If the previous value of the clock sequence is known, it
 * > MAY be incremented; otherwise it SHOULD be set to a random or high-quality pseudorandom value.
 * >
 * > Similarly, if the Node ID changes (e.g., because a network card has been moved between machines), setting the clock
 * > sequence to a random number minimizes the probability of a duplicate due to slight differences in the clock
 * > settings of the machines. If the value of the clock sequence associated with the changed Node ID were known, then
 * > the clock sequence MAY be incremented, but that is unlikely.
 * >
 * > The clock sequence MUST be originally (i.e., once in the lifetime of a system) initialized to a random number to
 * > minimize the correlation across systems. This provides maximum protection against Node IDs that may move or switch
 * > from system to system rapidly. The initial value MUST NOT be correlated to the Node ID.
 *
 * From RFC 9562, section 5.6.:
 *
 * > The clock sequence and node bits SHOULD be reset to a pseudorandom value for each new UUIDv6 generated; however,
 * > implementations MAY choose to retain the old clock sequence and MAC address behavior from Section 5.1.
 *
 * @link https://www.rfc-editor.org/rfc/rfc9562 RFC 9562.
 */
interface ClockSequence extends Sequence
{
    /**
     * @param non-empty-string | null $state For a clock sequence, the state typically identifies the machine or node.
     *     This may be the MAC address, or it may be some other identifier, according to the application's needs.
     * @param DateTimeInterface | null $dateTime The date-time value is used together with the state value to keep track
     *     of the clock sequence.
     *
     * @return int<0, max> Clock sequence values are always greater than or equal to zero
     */
    public function current(?string $state = null, ?DateTimeInterface $dateTime = null): int;

    /**
     * {@inheritDoc}
     *
     * Please be aware that, in some clock sequence implementations, this value may not advance except under certain
     * conditions. For example, in {@see Rfc4122ClockSequence}, if the state hasn't changed, and the date-time value
     * is later than the previous date-time value, then the clock sequence does not advance.
     *
     * @param non-empty-string | null $state For a clock sequence, the state typically identifies the machine or node.
     *     This may be the MAC address, or it may be some other identifier, according to the application's needs.
     * @param DateTimeInterface | null $dateTime The date-time value is used together with the state value to keep track
     *     of the clock sequence. If this value is less than or equal to a previously used clock value, the sequence
     *     should increment the clock sequence value, since the clock has been set backwards.
     *
     * @return int<0, max> Clock sequence values are always greater than or equal to zero.
     *
     * @throws SequenceOverflow if the maximum clock sequence value is reached.
     */
    public function next(?string $state = null, ?DateTimeInterface $dateTime = null): int;
}
