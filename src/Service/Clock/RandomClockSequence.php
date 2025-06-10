<?php

declare(strict_types=1);

namespace Ramsey\Identifier\Service\Clock;

use DateTimeInterface;
use Ramsey\Identifier\Service\Sequence\RandomSequence;

/**
 * A clock sequence that is always randomly generated and does not use stable storage.
 */
final class RandomClockSequence implements ClockSequence
{
    private RandomSequence $sequence;

    public function __construct()
    {
        $this->sequence = new RandomSequence(min: 0);
    }

    public function current(?string $state = null, ?DateTimeInterface $dateTime = null): int
    {
        /** @var int<0, max> */
        return $this->sequence->current();
    }

    public function next(?string $state = null, ?DateTimeInterface $dateTime = null): int
    {
        /** @var int<0, max> */
        return $this->sequence->next();
    }
}
