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
use Psr\SimpleCache\CacheInterface;

use function random_int;

use const PHP_INT_MAX;

/**
 * Maintains the state of the node and date-time values to calculate the clock
 * sequence value
 */
final class StatefulSequence implements Sequence
{
    private const NODE_CACHE_KEY = '__ramsey_id_last_node';
    private const TIME_CACHE_KEY = '__ramsey_id_last_time';

    /**
     * @var int<0, max>
     */
    private static int $clockSeq;

    private static ?string $lastNode = null;
    private static ?DateTimeInterface $lastTime = null;

    /**
     * @param int<0, max> | null $initialClockSeq An initial clock sequence value.
     * @param CacheInterface | null $cache An optional PSR-16 cache instance to
     *     cache the last node and last time. Be aware that use of a centralized
     *     cache might have unintended consequences and could cause collisions.
     *     To mitigate this, use a machine-local cache, such as APCu.
     */
    public function __construct(
        ?int $initialClockSeq = null,
        private readonly ?CacheInterface $cache = null,
    ) {
        self::$clockSeq = $initialClockSeq ?? random_int(0, PHP_INT_MAX);

        /** @var string | null $lastNode */
        $lastNode = $this->cache?->get(self::NODE_CACHE_KEY);
        self::$lastNode = $lastNode;

        /** @var string | null $lastTime */
        $lastTime = $this->cache?->get(self::TIME_CACHE_KEY);
        self::$lastTime = $lastTime !== null ? new DateTimeImmutable($lastTime) : null;
    }

    public function __destruct()
    {
        if (self::$lastNode !== null) {
            $this->cache?->set(self::NODE_CACHE_KEY, self::$lastNode);
        }

        if (self::$lastTime !== null) {
            $this->cache?->set(self::TIME_CACHE_KEY, self::$lastTime->format('@U.u'));
        }
    }

    public function value(string $node, DateTimeInterface $dateTime): int
    {
        if (self::$lastNode !== null && $node !== self::$lastNode) {
            // If the node has changed, regenerate the clock sequence.
            self::$clockSeq = random_int(0, PHP_INT_MAX);
        }

        if (self::$lastTime !== null && $dateTime->format('Uu') <= self::$lastTime->format('Uu')) {
            if (self::$clockSeq === PHP_INT_MAX) {
                // Roll over the clock sequence.
                self::$clockSeq = 0;
            } else {
                self::$clockSeq++;
            }
        }

        self::$lastNode = $node;
        self::$lastTime = clone $dateTime;

        return self::$clockSeq;
    }
}