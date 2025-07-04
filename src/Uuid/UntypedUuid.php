<?php

/**
 * This file is part of ramsey/identifier
 *
 * ramsey/identifier is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser
 * General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.
 *
 * ramsey/identifier is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the
 * implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License
 * for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License along with ramsey/identifier. If not, see
 * <https://www.gnu.org/licenses/>.
 *
 * @copyright Copyright (c) Ben Ramsey <ben@ramsey.dev> and Contributors
 * @license https://opensource.org/license/lgpl-3-0/ GNU Lesser General Public License version 3 or later
 */

declare(strict_types=1);

namespace Ramsey\Identifier\Uuid;

use DateTimeImmutable;
use Ramsey\Identifier\Exception\BadMethodCall;
use Ramsey\Identifier\Exception\CannotDetermineVersion;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\NodeBasedUuid;
use Ramsey\Identifier\TimeBasedUuid;
use Ramsey\Identifier\Uuid\Internal\Format;
use Ramsey\Identifier\Uuid\Internal\Mask;
use Ramsey\Identifier\Uuid\Internal\Standard;

use function assert;
use function preg_match;
use function sprintf;
use function strlen;
use function strspn;

/**
 * An untyped UUID is one in which the version and variant bits have not yet been evaluated. This does not mean the UUID
 * is invalid; rather, this is a performance feature.
 *
 * When creating a UUID instance from string, bytes, hexadecimal, or integer, it is more performant to defer checking
 * the version and variant bits until later, i.e., when calling {@see self::getVersion()}, {@see self::getVariant()},
 * {@see self::getDateTime()}, and {@see self::getNode()}.
 *
 * To access a typed version (e.g., {@see UuidV4}, {@see UuidV7}, etc.), call {@see self::toTypedUuid()} on any
 * UntypedUuid instance.
 */
final class UntypedUuid implements NodeBasedUuid, TimeBasedUuid
{
    use Standard;

    private const VALID_UUID = '/\A[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\z/i';

    private ?Variant $variant = null;
    private ?Version $version = null;

    private MaxUuid | MicrosoftGuid | NilUuid | NonstandardUuid | UuidV1 | UuidV2 | UuidV3 | UuidV4 | UuidV5 | UuidV6 | UuidV7 | UuidV8 | null $typedUuid = null; // phpcs:ignore Generic.Files.LineLength

    /**
     * @param non-empty-string $uuid A representation of the UUID as a string with dashes, hexadecimal, or byte string.
     *
     * @throws InvalidArgument
     */
    public function __construct(private readonly string $uuid)
    {
        $this->format = Format::tryFrom(strlen($this->uuid));

        if (!$this->isValid($this->uuid, $this->format)) {
            throw new InvalidArgument(sprintf('Invalid UUID: "%s"', $this->uuid));
        }
    }

    /**
     * @throws BadMethodCall
     */
    public function getDateTime(): DateTimeImmutable
    {
        $uuid = $this->toTypedUuid();

        if ($uuid instanceof TimeBasedUuid) {
            return $uuid->getDateTime();
        }

        throw new BadMethodCall(sprintf(
            'Cannot call getDateTime() on untyped UUID "%s"; it is not a time-based UUID',
            $this->getFormat(Format::String),
        ));
    }

    /**
     * @throws BadMethodCall
     */
    public function getNode(): string
    {
        $uuid = $this->toTypedUuid();

        if ($uuid instanceof NodeBasedUuid) {
            return $uuid->getNode();
        }

        throw new BadMethodCall(sprintf(
            'Cannot call getNode() on untyped UUID "%s"; it is not a node-based UUID',
            $this->getFormat(Format::String),
        ));
    }

    public function getVariant(): Variant
    {
        if ($this->variant === null) {
            if ($this->isMax($this->uuid, $this->format) || $this->isNil($this->uuid, $this->format)) {
                $this->variant = Variant::Rfc;
            } else {
                $this->variant = $this->getVariantFromUuid($this->uuid, $this->format);
            }
        }

        assert($this->variant !== null);

        return $this->variant;
    }

    /**
     * @throws CannotDetermineVersion
     */
    public function getVersion(): Version
    {
        $variant = $this->getVariant();

        if ($this->version === null && ($variant === Variant::Rfc || $variant === Variant::Microsoft)) {
            $this->version = Version::tryFrom(
                (int) $this->getVersionFromUuid($this->uuid, $this->format, $variant === Variant::Microsoft),
            );
        }

        return $this->version
            ?? throw new CannotDetermineVersion(sprintf(
                'Unable to determine version of untyped UUID "%s"',
                $this->getFormat(Format::String),
            ));
    }

    /**
     * Returns a typed version of this UUID
     */
    public function toTypedUuid(): MaxUuid | MicrosoftGuid | NilUuid | NonstandardUuid | UuidV1 | UuidV2 | UuidV3 | UuidV4 | UuidV5 | UuidV6 | UuidV7 | UuidV8 // phpcs:ignore Generic.Files.LineLength
    {
        if ($this->typedUuid === null) {
            try {
                $version = $this->getVersion();
            } catch (CannotDetermineVersion) {
                $version = null;
            }

            $variant = $this->getVariant();

            $this->typedUuid = match (true) {
                $version === Version::V1 && $variant === Variant::Rfc => new UuidV1($this->uuid),
                $version === Version::V2 && $variant === Variant::Rfc => (
                    static function (string $uuid): UuidV2 | NonstandardUuid {
                        try {
                            return new UuidV2($uuid);
                        } catch (InvalidArgument) {
                            return new NonstandardUuid($uuid);
                        }
                    }
                )($this->uuid),
                $version === Version::V3 && $variant === Variant::Rfc => new UuidV3($this->uuid),
                $version === Version::V4 && $variant === Variant::Rfc => new UuidV4($this->uuid),
                $version === Version::V5 && $variant === Variant::Rfc => new UuidV5($this->uuid),
                $version === Version::V6 && $variant === Variant::Rfc => new UuidV6($this->uuid),
                $version === Version::V7 && $variant === Variant::Rfc => new UuidV7($this->uuid),
                $version === Version::V8 && $variant === Variant::Rfc => new UuidV8($this->uuid),
                $version !== null && $variant === Variant::Microsoft => new MicrosoftGuid($this->uuid),
                $this->isMax($this->uuid, $this->format) => new MaxUuid(),
                $this->isNil($this->uuid, $this->format) => new NilUuid(),
                default => new NonstandardUuid($this->uuid),
            };
        }

        return $this->typedUuid;
    }

    private function isValid(string $uuid, ?Format $format): bool
    {
        return match ($format) {
            Format::Bytes => strlen($uuid) === 16,
            Format::Hex => strspn($uuid, Mask::HEX) === 32,
            Format::String => preg_match(self::VALID_UUID, $uuid) === 1,
            default => false,
        };
    }
}
