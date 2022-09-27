<?php

declare(strict_types=1);

namespace Ramsey\Bench\Identifier\Uuid;

use Ramsey\Identifier\Uuid\UuidV6;

final class UuidV6Bench
{
    private UuidV6 $uuidWithBytes;
    private UuidV6 $uuidWithHex;
    private UuidV6 $uuidWithString;

    public function __construct()
    {
        $this->uuidWithBytes = new UuidV6("\x0a\xe0\xca\xc5\x2a\x40\x66\x5c\x99\xed\x3d\x33\x1b\x7c\xf7\x2a");
        $this->uuidWithHex = new UuidV6('0ae0cac52a40665c99ed3d331b7cf72a');
        $this->uuidWithString = new UuidV6('0ae0cac5-2a40-665c-99ed-3d331b7cf72a');
    }

    public function benchGetDateTimeForBytesUuid(): void
    {
        $this->uuidWithBytes->getDateTime();
    }

    public function benchGetDateTimeForHexUuid(): void
    {
        $this->uuidWithHex->getDateTime();
    }

    public function benchGetDateTimeForStringUuid(): void
    {
        $this->uuidWithString->getDateTime();
    }

    public function benchGetNodeForBytesUuid(): void
    {
        $this->uuidWithBytes->getNode();
    }

    public function benchGetNodeForHexUuid(): void
    {
        $this->uuidWithHex->getNode();
    }

    public function benchGetNodeForStringUuid(): void
    {
        $this->uuidWithString->getNode();
    }

    public function benchToBytesForBytesUuid(): void
    {
        $this->uuidWithBytes->toBytes();
    }

    public function benchToBytesForHexUuid(): void
    {
        $this->uuidWithHex->toBytes();
    }

    public function benchToBytesForStringUuid(): void
    {
        $this->uuidWithString->toBytes();
    }

    public function benchToHexadecimalForBytesUuid(): void
    {
        $this->uuidWithBytes->toHexadecimal();
    }

    public function benchToHexadecimalForHexUuid(): void
    {
        $this->uuidWithHex->toHexadecimal();
    }

    public function benchToHexadecimalForStringUuid(): void
    {
        $this->uuidWithString->toHexadecimal();
    }

    public function benchToIntegerForBytesUuid(): void
    {
        $this->uuidWithBytes->toInteger();
    }

    public function benchToIntegerForHexUuid(): void
    {
        $this->uuidWithHex->toInteger();
    }

    public function benchToIntegerForStringUuid(): void
    {
        $this->uuidWithString->toInteger();
    }

    public function benchToStringForBytesUuid(): void
    {
        $this->uuidWithBytes->toString();
    }

    public function benchToStringForHexUuid(): void
    {
        $this->uuidWithHex->toString();
    }

    public function benchToStringForStringUuid(): void
    {
        $this->uuidWithString->toString();
    }

    public function benchToUrnForBytesUuid(): void
    {
        $this->uuidWithBytes->toUrn();
    }

    public function benchToUrnForHexUuid(): void
    {
        $this->uuidWithHex->toUrn();
    }

    public function benchToUrnForStringUuid(): void
    {
        $this->uuidWithString->toUrn();
    }
}
