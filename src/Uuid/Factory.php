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

use BadMethodCallException;
use Brick\Math\BigInteger;
use Brick\Math\Exception\MathException;
use Brick\Math\Exception\NegativeNumberException;
use DateTimeInterface;
use Identifier\Uuid\UuidInterface;
use Identifier\Uuid\Variant;
use Ramsey\Identifier\Exception\CacheException;
use Ramsey\Identifier\Exception\DceSecurityException;
use Ramsey\Identifier\Exception\InvalidArgumentException;
use Ramsey\Identifier\Exception\NodeNotFoundException;
use Ramsey\Identifier\Exception\RandomSourceException;
use Ramsey\Identifier\Service\ClockSequence\ClockSequenceServiceInterface;
use Ramsey\Identifier\Service\ClockSequence\RandomClockSequenceService;
use Ramsey\Identifier\Service\DceSecurity\DceSecurityServiceInterface;
use Ramsey\Identifier\Service\DceSecurity\SystemDceSecurityService;
use Ramsey\Identifier\Service\Node\FallbackNodeService;
use Ramsey\Identifier\Service\Node\NodeServiceInterface;
use Ramsey\Identifier\Service\Node\RandomNodeService;
use Ramsey\Identifier\Service\Node\SystemNodeService;
use Ramsey\Identifier\Service\Random\RandomBytesService;
use Ramsey\Identifier\Service\Random\RandomServiceInterface;
use Ramsey\Identifier\Service\Time\CurrentDateTimeService;
use Ramsey\Identifier\Service\Time\TimeServiceInterface;

use function hexdec;
use function is_int;
use function is_string;
use function pack;
use function sprintf;
use function str_pad;
use function strlen;
use function strspn;
use function unpack;

use const PHP_INT_MAX;
use const PHP_INT_SIZE;
use const STR_PAD_LEFT;

/**
 * A default factory for creating UUIDs
 */
final class Factory implements FactoryInterface
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
     * @param ClockSequenceServiceInterface $clockSequenceService A service used
     *     to generate a clock sequence; defaults to
     *     {@see RandomClockSequenceService}
     * @param DceSecurityServiceInterface $dceSecurityService A service used
     *     to get local identifiers when creating version 2 UUIDs; defaults to
     *     {@see SystemDceSecurityService}
     * @param NodeServiceInterface $nodeService A service used to provide the
     *     system node; defaults to {@see FallbackNodeService} with
     *     {@see SystemNodeService} and {@see RandomNodeService}, as a fallback
     * @param RandomServiceInterface $randomService A service used to generate
     *     random bytes; defaults to {@see RandomBytesService}
     * @param TimeServiceInterface $timeService A service used to provide a
     *     date-time instance; defaults to {@see CurrentDateTimeService}
     */
    public function __construct(
        ClockSequenceServiceInterface $clockSequenceService = new RandomClockSequenceService(),
        DceSecurityServiceInterface $dceSecurityService = new SystemDceSecurityService(),
        NodeServiceInterface $nodeService = new FallbackNodeService([
            new SystemNodeService(),
            new RandomNodeService(),
        ]),
        RandomServiceInterface $randomService = new RandomBytesService(),
        TimeServiceInterface $timeService = new CurrentDateTimeService(),
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
     * @throws InvalidArgumentException
     * @throws RandomSourceException
     */
    public function create(): UuidV4
    {
        return $this->uuidV4Factory->create();
    }

    /**
     * @throws InvalidArgumentException
     */
    public function createFromBytes(string $identifier): UuidInterface
    {
        if (strlen($identifier) === Util::FORMAT_BYTES) {
            /** @var int[] $parts */
            $parts = unpack('C', $identifier[6]);
            $version = $parts[1] >> 4;
            $variant = $this->getVariantFromUuid($identifier, Util::FORMAT_BYTES);

            return $this->newUuid($identifier, $version, $variant, Util::FORMAT_BYTES);
        }

        throw new InvalidArgumentException('Identifier must be a 16-byte string');
    }

    /**
     * @throws InvalidArgumentException
     */
    public function createFromHexadecimal(string $identifier): UuidInterface
    {
        if (strlen($identifier) === Util::FORMAT_HEX && $this->hasValidFormat($identifier, Util::FORMAT_HEX)) {
            $variant = $this->getVariantFromUuid($identifier, Util::FORMAT_HEX);
            $version = (int) hexdec($identifier[12]);

            return $this->newUuid($identifier, $version, $variant, Util::FORMAT_HEX);
        }

        throw new InvalidArgumentException('Identifier must be a 32-character hexadecimal string');
    }

    /**
     * @throws InvalidArgumentException
     */
    public function createFromInteger(int | string $identifier): UuidInterface
    {
        if (
            is_string($identifier)
            && strspn($identifier, Util::MASK_INT) === strlen($identifier)
            && $identifier <= (string) PHP_INT_MAX
        ) {
            $identifier = (int) $identifier;
        }

        if (is_int($identifier)) {
            if ($identifier < 0) {
                throw new InvalidArgumentException('Unable to create a UUID from a negative integer');
            }

            $bytes = pack(PHP_INT_SIZE >= 8 ? 'J*' : 'N*', $identifier);
        } else {
            try {
                $bytes = BigInteger::of($identifier)->toBytes(false);
            } catch (NegativeNumberException $exception) {
                throw new InvalidArgumentException('Unable to create a UUID from a negative integer', 0, $exception);
            } catch (MathException $exception) {
                throw new InvalidArgumentException(sprintf('Invalid integer: "%s"', $identifier), 0, $exception);
            }
        }

        return $this->createFromBytes(str_pad($bytes, 16, "\x00", STR_PAD_LEFT));
    }

    /**
     * @throws InvalidArgumentException
     */
    public function createFromString(string $identifier): UuidInterface
    {
        if (strlen($identifier) === 36 && $this->hasValidFormat($identifier, Util::FORMAT_STRING)) {
            $variant = $this->getVariantFromUuid($identifier, Util::FORMAT_STRING);
            $version = (int) hexdec($identifier[14]);

            return $this->newUuid($identifier, $version, $variant, Util::FORMAT_STRING);
        }

        throw new InvalidArgumentException('Identifier must be a UUID in string standard representation');
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
     * @throws InvalidArgumentException
     * @throws NodeNotFoundException
     * @throws RandomSourceException
     */
    public function uuid1(
        int | string | null $node = null,
        ?int $clockSequence = null,
        ?DateTimeInterface $dateTime = null,
    ): UuidV1 {
        return $this->uuidV1Factory->create($node, $clockSequence, $dateTime);
    }

    /**
     * @throws CacheException
     * @throws DceSecurityException
     * @throws InvalidArgumentException
     * @throws NodeNotFoundException
     * @throws RandomSourceException
     */
    public function uuid2(
        Dce\Domain $localDomain = Dce\Domain::Person,
        ?int $localIdentifier = null,
        int | string | null $node = null,
        ?int $clockSequence = null,
        ?DateTimeInterface $dateTime = null,
    ): UuidV2 {
        return $this->uuidV2Factory->create($localDomain, $localIdentifier, $node, $clockSequence, $dateTime);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function uuid3(string | UuidInterface $namespace, string $name): UuidV3
    {
        if (!$namespace instanceof UuidInterface) {
            $namespace = match (strlen($namespace)) {
                Util::FORMAT_STRING => $this->createFromString($namespace),
                Util::FORMAT_HEX => $this->createFromHexadecimal($namespace),
                Util::FORMAT_BYTES => $this->createFromBytes($namespace),
                default => throw new InvalidArgumentException(sprintf('Invalid UUID namespace: "%s"', $namespace)),
            };
        }

        return $this->uuidV3Factory->create($namespace, $name);
    }

    /**
     * @throws InvalidArgumentException
     * @throws RandomSourceException
     */
    public function uuid4(): UuidV4
    {
        return $this->uuidV4Factory->create();
    }

    /**
     * @throws InvalidArgumentException
     */
    public function uuid5(string | UuidInterface $namespace, string $name): UuidV5
    {
        if (!$namespace instanceof UuidInterface) {
            $namespace = match (strlen($namespace)) {
                Util::FORMAT_STRING => $this->createFromString($namespace),
                Util::FORMAT_HEX => $this->createFromHexadecimal($namespace),
                Util::FORMAT_BYTES => $this->createFromBytes($namespace),
                default => throw new InvalidArgumentException(sprintf('Invalid UUID namespace: "%s"', $namespace)),
            };
        }

        return $this->uuidV5Factory->create($namespace, $name);
    }

    /**
     * @throws InvalidArgumentException
     * @throws NodeNotFoundException
     * @throws RandomSourceException
     */
    public function uuid6(
        int | string | null $node = null,
        ?int $clockSequence = null,
        ?DateTimeInterface $dateTime = null,
    ): UuidV6 {
        return $this->uuidV6Factory->create($node, $clockSequence, $dateTime);
    }

    /**
     * @throws InvalidArgumentException
     * @throws RandomSourceException
     */
    public function uuid7(?DateTimeInterface $dateTime = null): UuidV7
    {
        return $this->uuidV7Factory->create($dateTime);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function uuid8(string $customFieldA, string $customFieldB, string $customFieldC): UuidV8
    {
        return $this->uuidV8Factory->create($customFieldA, $customFieldB, $customFieldC);
    }

    /**
     * @throws BadMethodCallException
     *
     * @psalm-mutation-free
     */
    protected function getVersion(): never
    {
        throw new BadMethodCallException('Unable to call getVersion() on UuidFactory'); // @codeCoverageIgnore
    }

    /**
     * Returns a new instance of a UuidInterface for the given identifier
     *
     * @throws InvalidArgumentException
     */
    private function newUuid(string $identifier, int $version, ?Variant $variant, int $format): UuidInterface
    {
        return match (true) {
            $version === 1 && $variant === Variant::Rfc4122 => new UuidV1($identifier),
            $version === 2 && $variant === Variant::Rfc4122 => new UuidV2($identifier),
            $version === 3 && $variant === Variant::Rfc4122 => new UuidV3($identifier),
            $version === 4 && $variant === Variant::Rfc4122 => new UuidV4($identifier),
            $version === 5 && $variant === Variant::Rfc4122 => new UuidV5($identifier),
            $version === 6 && $variant === Variant::Rfc4122 => new UuidV6($identifier),
            $version === 7 && $variant === Variant::Rfc4122 => new UuidV7($identifier),
            $version === 8 && $variant === Variant::Rfc4122 => new UuidV8($identifier),
            $this->isMax($identifier, $format) => new MaxUuid(),
            $this->isNil($identifier, $format) => new NilUuid(),
            default => new NonstandardUuid($identifier),
        };
    }
}
