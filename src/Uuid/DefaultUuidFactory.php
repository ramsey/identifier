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

use Brick\Math\BigInteger;
use Brick\Math\Exception\MathException;
use Brick\Math\Exception\NegativeNumberException;
use DateTimeInterface;
use Ramsey\Identifier\Exception\BadMethodCall;
use Ramsey\Identifier\Exception\DceSecurityIdentifierNotFound;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Exception\InvalidCacheKey;
use Ramsey\Identifier\Exception\NodeNotFound;
use Ramsey\Identifier\Exception\RandomSourceNotFound;
use Ramsey\Identifier\Service\ClockSequence\ClockSequenceService;
use Ramsey\Identifier\Service\ClockSequence\RandomClockSequenceService;
use Ramsey\Identifier\Service\DateTime\CurrentDateTimeService;
use Ramsey\Identifier\Service\DateTime\DateTimeService;
use Ramsey\Identifier\Service\DceSecurity\DceSecurityService;
use Ramsey\Identifier\Service\DceSecurity\SystemDceSecurityService;
use Ramsey\Identifier\Service\Node\FallbackNodeService;
use Ramsey\Identifier\Service\Node\NodeService;
use Ramsey\Identifier\Service\Node\RandomNodeService;
use Ramsey\Identifier\Service\Node\SystemNodeService;
use Ramsey\Identifier\Service\Random\RandomBytesService;
use Ramsey\Identifier\Service\Random\RandomService;
use Ramsey\Identifier\Uuid\Utility\Format;
use Ramsey\Identifier\Uuid\Utility\Validation;
use Ramsey\Identifier\UuidFactory;
use Ramsey\Identifier\UuidIdentifier;

use function is_int;
use function is_string;
use function pack;
use function sprintf;
use function str_pad;
use function strlen;
use function strspn;

use const PHP_INT_MAX;
use const PHP_INT_SIZE;
use const STR_PAD_LEFT;

/**
 * A default factory for creating UUIDs
 */
final class DefaultUuidFactory implements UuidFactory
{
    use Validation;

    private readonly UuidV1Factory $uuidV1Factory;
    private readonly UuidV2Factory $uuidV2Factory;
    private readonly UuidV3Factory $uuidV3Factory;
    private readonly UuidV4Factory $uuidV4Factory;
    private readonly UuidV5Factory $uuidV5Factory;
    private readonly UuidV6Factory $uuidV6Factory;
    private readonly UuidV7Factory $uuidV7Factory;
    private readonly UuidV8Factory $uuidV8Factory;

    /**
     * Constructs a default factory for creating UUIDs
     *
     * @param ClockSequenceService $clockSequenceService A service used
     *     to generate a clock sequence; defaults to
     *     {@see RandomClockSequenceService}
     * @param DceSecurityService $dceSecurityService A service used
     *     to get local identifiers when creating version 2 UUIDs; defaults to
     *     {@see SystemDceSecurityService}
     * @param NodeService $nodeService A service used to provide the
     *     system node; defaults to {@see FallbackNodeService} with
     *     {@see SystemNodeService} and {@see RandomNodeService}, as a fallback
     * @param RandomService $randomService A service used to generate
     *     random bytes; defaults to {@see RandomBytesService}
     * @param DateTimeService $timeService A service used to provide a
     *     date-time instance; defaults to {@see CurrentDateTimeService}
     */
    public function __construct(
        ClockSequenceService $clockSequenceService = new RandomClockSequenceService(),
        DceSecurityService $dceSecurityService = new SystemDceSecurityService(),
        NodeService $nodeService = new FallbackNodeService([
            new SystemNodeService(),
            new RandomNodeService(),
        ]),
        RandomService $randomService = new RandomBytesService(),
        DateTimeService $timeService = new CurrentDateTimeService(),
    ) {
        $this->uuidV1Factory = new UuidV1Factory($clockSequenceService, $nodeService, $timeService);
        $this->uuidV2Factory = new UuidV2Factory(
            $clockSequenceService,
            $dceSecurityService,
            $nodeService,
            $timeService,
        );
        $this->uuidV3Factory = new UuidV3Factory();
        $this->uuidV4Factory = new UuidV4Factory($randomService);
        $this->uuidV5Factory = new UuidV5Factory();
        $this->uuidV6Factory = new UuidV6Factory($clockSequenceService, $nodeService, $timeService);
        $this->uuidV7Factory = new UuidV7Factory($randomService, $timeService);
        $this->uuidV8Factory = new UuidV8Factory();
    }

    /**
     * @throws RandomSourceNotFound
     */
    public function create(): UuidV4
    {
        return $this->uuidV4Factory->create();
    }

    /**
     * @throws InvalidArgument
     */
    public function createFromBytes(string $identifier): UntypedUuid
    {
        if (strlen($identifier) === Format::FORMAT_BYTES) {
            return new UntypedUuid($identifier);
        }

        throw new InvalidArgument('Identifier must be a 16-byte string');
    }

    /**
     * @throws InvalidArgument
     */
    public function createFromHexadecimal(string $identifier): UntypedUuid
    {
        if (strlen($identifier) === Format::FORMAT_HEX) {
            return new UntypedUuid($identifier);
        }

        throw new InvalidArgument('Identifier must be a 32-character hexadecimal string');
    }

    /**
     * @throws InvalidArgument
     */
    public function createFromInteger(int | string $identifier): UntypedUuid
    {
        if (
            is_string($identifier)
            && strspn($identifier, Format::MASK_INT) === strlen($identifier)
            && $identifier <= (string) PHP_INT_MAX
        ) {
            $identifier = (int) $identifier;
        }

        if (is_int($identifier)) {
            if ($identifier < 0) {
                throw new InvalidArgument('Unable to create a UUID from a negative integer');
            }

            $bytes = pack(PHP_INT_SIZE >= 8 ? 'J*' : 'N*', $identifier);
        } else {
            try {
                $bytes = BigInteger::of($identifier)->toBytes(false);
            } catch (NegativeNumberException $exception) {
                throw new InvalidArgument('Unable to create a UUID from a negative integer', 0, $exception);
            } catch (MathException $exception) {
                throw new InvalidArgument(sprintf('Invalid integer: "%s"', $identifier), 0, $exception);
            }
        }

        return $this->createFromBytes(str_pad($bytes, 16, "\x00", STR_PAD_LEFT));
    }

    /**
     * @throws InvalidArgument
     */
    public function createFromString(string $identifier): UntypedUuid
    {
        if (strlen($identifier) === 36) {
            return new UntypedUuid($identifier);
        }

        throw new InvalidArgument('Identifier must be a UUID in string standard representation');
    }

    public function max(): MaxUuid
    {
        return new MaxUuid();
    }

    public function nil(): NilUuid
    {
        return new NilUuid();
    }

    /**
     * @throws InvalidArgument
     * @throws NodeNotFound
     * @throws RandomSourceNotFound
     */
    public function v1(
        int | string | null $node = null,
        ?int $clockSequence = null,
        ?DateTimeInterface $dateTime = null,
    ): UuidV1 {
        return $this->uuidV1Factory->create($node, $clockSequence, $dateTime);
    }

    /**
     * @throws InvalidCacheKey
     * @throws DceSecurityIdentifierNotFound
     * @throws InvalidArgument
     * @throws NodeNotFound
     * @throws RandomSourceNotFound
     */
    public function v2(
        DceDomain $localDomain = DceDomain::Person,
        ?int $localIdentifier = null,
        int | string | null $node = null,
        ?int $clockSequence = null,
        ?DateTimeInterface $dateTime = null,
    ): UuidV2 {
        return $this->uuidV2Factory->create($localDomain, $localIdentifier, $node, $clockSequence, $dateTime);
    }

    /**
     * @throws InvalidArgument
     */
    public function v3(string | UuidIdentifier $namespace, string $name): UuidV3
    {
        if (!$namespace instanceof UuidIdentifier) {
            $namespace = match (strlen($namespace)) {
                Format::FORMAT_STRING => $this->createFromString($namespace),
                Format::FORMAT_HEX => $this->createFromHexadecimal($namespace),
                Format::FORMAT_BYTES => $this->createFromBytes($namespace),
                default => throw new InvalidArgument(sprintf('Invalid UUID namespace: "%s"', $namespace)),
            };
        }

        return $this->uuidV3Factory->create($namespace, $name);
    }

    /**
     * @throws RandomSourceNotFound
     */
    public function v4(): UuidV4
    {
        return $this->uuidV4Factory->create();
    }

    /**
     * @throws InvalidArgument
     */
    public function v5(string | UuidIdentifier $namespace, string $name): UuidV5
    {
        if (!$namespace instanceof UuidIdentifier) {
            $namespace = match (strlen($namespace)) {
                Format::FORMAT_STRING => $this->createFromString($namespace),
                Format::FORMAT_HEX => $this->createFromHexadecimal($namespace),
                Format::FORMAT_BYTES => $this->createFromBytes($namespace),
                default => throw new InvalidArgument(sprintf('Invalid UUID namespace: "%s"', $namespace)),
            };
        }

        return $this->uuidV5Factory->create($namespace, $name);
    }

    /**
     * @throws InvalidArgument
     * @throws NodeNotFound
     * @throws RandomSourceNotFound
     */
    public function v6(
        int | string | null $node = null,
        ?int $clockSequence = null,
        ?DateTimeInterface $dateTime = null,
    ): UuidV6 {
        return $this->uuidV6Factory->create($node, $clockSequence, $dateTime);
    }

    /**
     * @throws InvalidArgument
     * @throws RandomSourceNotFound
     */
    public function v7(?DateTimeInterface $dateTime = null): UuidV7
    {
        return $this->uuidV7Factory->create($dateTime);
    }

    /**
     * @throws InvalidArgument
     */
    public function v8(string $customFieldA, string $customFieldB, string $customFieldC): UuidV8
    {
        return $this->uuidV8Factory->create($customFieldA, $customFieldB, $customFieldC);
    }

    /**
     * @throws BadMethodCall
     *
     * @psalm-mutation-free
     */
    protected function getVersion(): never
    {
        throw new BadMethodCall('Unable to call getVersion() on UuidFactory'); // @codeCoverageIgnore
    }
}
