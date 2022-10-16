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
use Identifier\BinaryIdentifierFactory;
use Identifier\IntegerIdentifierFactory;
use Identifier\StringIdentifierFactory;
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
final class UuidFactory implements BinaryIdentifierFactory, IntegerIdentifierFactory, StringIdentifierFactory
{
    use Validation;

    private readonly Factory\UuidV1Factory $uuidV1Factory;
    private readonly Factory\UuidV2Factory $uuidV2Factory;
    private readonly Factory\UuidV3Factory $uuidV3Factory;
    private readonly Factory\UuidV4Factory $uuidV4Factory;
    private readonly Factory\UuidV5Factory $uuidV5Factory;
    private readonly Factory\UuidV6Factory $uuidV6Factory;
    private readonly Factory\UuidV7Factory $uuidV7Factory;
    private readonly Factory\UuidV8Factory $uuidV8Factory;

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
        $this->uuidV1Factory = new Factory\UuidV1Factory($clockSequenceService, $nodeService, $timeService);
        $this->uuidV2Factory = new Factory\UuidV2Factory(
            $clockSequenceService,
            $dceSecurityService,
            $nodeService,
            $timeService,
        );
        $this->uuidV3Factory = new Factory\UuidV3Factory();
        $this->uuidV4Factory = new Factory\UuidV4Factory($randomService);
        $this->uuidV5Factory = new Factory\UuidV5Factory();
        $this->uuidV6Factory = new Factory\UuidV6Factory($clockSequenceService, $nodeService, $timeService);
        $this->uuidV7Factory = new Factory\UuidV7Factory($randomService, $timeService);
        $this->uuidV8Factory = new Factory\UuidV8Factory();
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
     * Creates a new instance of a UUID from the given hexadecimal string representation
     *
     * @param string $identifier A hexadecimal string representation of the UUID
     *
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

    /**
     * Creates a Max UUID with all bits set to one (1)
     */
    public function max(): MaxUuid
    {
        return new MaxUuid();
    }

    /**
     * Creates a Nil UUID with all bits set to zero (0)
     */
    public function nil(): NilUuid
    {
        return new NilUuid();
    }

    /**
     * Creates a version 1, Gregorian time UUID
     *
     * @param int<0, max> | string | null $node A 48-bit integer or hexadecimal
     *     string representing the hardware address of the machine where this
     *     identifier was generated
     * @param int<0, 16383> | null $clockSequence A 14-bit number used to help
     *     avoid duplicates that could arise when the clock is set backwards in
     *     time or if the node ID changes
     * @param DateTimeInterface | null $dateTime A date-time to use when
     *     creating the identifier
     *
     * @throws InvalidArgument
     * @throws NodeNotFound
     * @throws RandomSourceNotFound
     *
     * @psalm-param int<0, max> | non-empty-string | null $node
     */
    public function uuid1(
        int | string | null $node = null,
        ?int $clockSequence = null,
        ?DateTimeInterface $dateTime = null,
    ): UuidV1 {
        return $this->uuidV1Factory->create($node, $clockSequence, $dateTime);
    }

    /**
     * Creates a version 2, DCE Security UUID
     *
     * @param DceDomain $localDomain The local domain to which the local
     *     identifier belongs; this defaults to "Person," and if $localIdentifier
     *     is not provided, the factory will attempt to obtain a suitable local
     *     ID for the domain (e.g., the UID or GID of the user running the script)
     * @param int<0, max> | null $localIdentifier A local identifier belonging
     *     to the local domain specified in $localDomain; if no identifier is
     *     provided, the factory will attempt to obtain a suitable local ID for
     *     the domain (e.g., the UID or GID of the user running the script)
     * @param int<0, max> | string | null $node A 48-bit integer or hexadecimal
     *     string representing the hardware address of the machine where this
     *     identifier was generated
     * @param int<0, 63> | null $clockSequence A 6-bit number used to help
     *     avoid duplicates that could arise when the clock is set backwards in
     *     time or if the node ID changes
     * @param DateTimeInterface | null $dateTime A date-time to use when
     *     creating the identifier
     *
     * @throws InvalidCacheKey
     * @throws DceSecurityIdentifierNotFound
     * @throws InvalidArgument
     * @throws NodeNotFound
     * @throws RandomSourceNotFound
     *
     * @psalm-param int<0, max> | non-empty-string | null $node
     */
    public function uuid2(
        DceDomain $localDomain = DceDomain::Person,
        ?int $localIdentifier = null,
        int | string | null $node = null,
        ?int $clockSequence = null,
        ?DateTimeInterface $dateTime = null,
    ): UuidV2 {
        return $this->uuidV2Factory->create($localDomain, $localIdentifier, $node, $clockSequence, $dateTime);
    }

    /**
     * Creates a version 3, name-based (MD5) UUID
     *
     * @param non-empty-string | Uuid $namespace
     *
     * @throws InvalidArgument
     */
    public function uuid3(string | Uuid $namespace, string $name): UuidV3
    {
        if (!$namespace instanceof Uuid) {
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
     * Creates a version 4, random UUID
     *
     * @throws RandomSourceNotFound
     */
    public function uuid4(): UuidV4
    {
        return $this->uuidV4Factory->create();
    }

    /**
     * Creates a version 5, name-based (SHA-1) UUID
     *
     * @param non-empty-string | Uuid $namespace
     *
     * @throws InvalidArgument
     */
    public function uuid5(string | Uuid $namespace, string $name): UuidV5
    {
        if (!$namespace instanceof Uuid) {
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
     * Creates a version 6, reordered time UUID
     *
     * @param int<0, max> | string | null $node A 48-bit integer or hexadecimal
     *     string representing the hardware address of the machine where this
     *     identifier was generated
     * @param int<0, 16383> | null $clockSequence A 14-bit number used to help
     *     avoid duplicates that could arise when the clock is set backwards in
     *     time or if the node ID changes
     * @param DateTimeInterface | null $dateTime A date-time to use when
     *     creating the identifier
     *
     * @throws InvalidArgument
     * @throws NodeNotFound
     * @throws RandomSourceNotFound
     *
     * @psalm-param int<0, max> | non-empty-string | null $node
     */
    public function uuid6(
        int | string | null $node = null,
        ?int $clockSequence = null,
        ?DateTimeInterface $dateTime = null,
    ): UuidV6 {
        return $this->uuidV6Factory->create($node, $clockSequence, $dateTime);
    }

    /**
     * Creates a version 7, Unix Epoch time UUID
     *
     * @param DateTimeInterface | null $dateTime A date-time to use when
     *     creating the identifier
     *
     * @throws InvalidArgument
     * @throws RandomSourceNotFound
     */
    public function uuid7(?DateTimeInterface $dateTime = null): UuidV7
    {
        return $this->uuidV7Factory->create($dateTime);
    }

    /**
     * Creates a version 8, custom UUID
     *
     * The three custom fields, A, B, and C, may contain any values according to
     * your application's needs. Be aware, however, that other implementations
     * may not understand the semantics of the values.
     *
     * @param string $customFieldA An arbitrary 48-bit (12-character)
     *     hexadecimal string
     * @param string $customFieldB An arbitrary 12-bit (3-character)
     *     hexadecimal string
     * @param string $customFieldC An arbitrary 64-bit (16-character)
     *     hexadecimal string (if set, the 2 most significant bits will be lost,
     *     since they are replaced with the variant bits, so don't rely on these
     *     bits to hold any important data; in other words, treat this as a
     *     62-bit value)
     *
     * @throws InvalidArgument
     */
    public function uuid8(string $customFieldA, string $customFieldB, string $customFieldC): UuidV8
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
