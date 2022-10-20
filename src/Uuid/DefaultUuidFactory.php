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
use Ramsey\Identifier\Exception\DceIdentifierNotFound;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Service\Clock\SystemClock;
use Ramsey\Identifier\Service\Counter\Counter;
use Ramsey\Identifier\Service\Counter\RandomCounter;
use Ramsey\Identifier\Service\Dce\Dce;
use Ramsey\Identifier\Service\Dce\SystemDce;
use Ramsey\Identifier\Service\Nic\Nic;
use Ramsey\Identifier\Service\Nic\RandomNic;
use Ramsey\Identifier\Service\RandomGenerator\PhpRandomGenerator;
use Ramsey\Identifier\Service\RandomGenerator\RandomGenerator;
use Ramsey\Identifier\Uuid\Utility\Format;
use Ramsey\Identifier\Uuid\Utility\Validation;
use Ramsey\Identifier\UuidFactory;
use Ramsey\Identifier\UuidIdentifier;
use StellaMaris\Clock\ClockInterface as Clock;

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
     * @param Clock $clock A clock used to provide a date-time instance;
     *     defaults to {@see SystemClock}
     * @param Counter $counter A counter that provides the next value in a
     *     sequence to prevent collisions in versions 1, 2, and 6 UUIDs;
     *     defaults to {@see RandomCounter}
     * @param Dce $dce A service that provides local identifiers when creating
     *     version 2 UUIDs; defaults to {@see SystemDce}
     * @param Nic $nic A NIC that provides the system MAC address value for
     *     versions 1, 2, and 6 UUIDs; defaults to {@see RandomNic}
     * @param RandomGenerator $randomGenerator A random generator used to
     *     generate bytes; defaults to {@see PhpRandomGenerator}
     */
    public function __construct(
        Clock $clock = new SystemClock(),
        Counter $counter = new RandomCounter(),
        Dce $dce = new SystemDce(),
        Nic $nic = new RandomNic(),
        RandomGenerator $randomGenerator = new PhpRandomGenerator(),
    ) {
        $this->uuidV1Factory = new UuidV1Factory($clock, $counter, $nic);
        $this->uuidV2Factory = new UuidV2Factory($clock, $counter, $dce, $nic);
        $this->uuidV3Factory = new UuidV3Factory();
        $this->uuidV4Factory = new UuidV4Factory($randomGenerator);
        $this->uuidV5Factory = new UuidV5Factory();
        $this->uuidV6Factory = new UuidV6Factory($clock, $counter, $nic);
        $this->uuidV7Factory = new UuidV7Factory($clock, $randomGenerator);
        $this->uuidV8Factory = new UuidV8Factory();
    }

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

            $bytes = pack(PHP_INT_SIZE >= 8 ? 'J' : 'N', $identifier);
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
     */
    public function v1(
        int | string | null $node = null,
        ?int $clockSequence = null,
        ?DateTimeInterface $dateTime = null,
    ): UuidV1 {
        return $this->uuidV1Factory->create($node, $clockSequence, $dateTime);
    }

    /**
     * @throws DceIdentifierNotFound
     * @throws InvalidArgument
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
     * @psalm-mutation-free
     */
    protected function getVersion(): never
    {
        throw new BadMethodCall('Unable to call getVersion() on UuidFactory'); // @codeCoverageIgnore
    }
}
