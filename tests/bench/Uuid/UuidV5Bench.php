<?php

declare(strict_types=1);

namespace Ramsey\Bench\Identifier\Uuid;

use Ramsey\Identifier\Uuid\UuidV5;

final class UuidV5Bench
{
    private UuidV5 $uuidWithBytes;
    private UuidV5 $uuidWithHex;
    private UuidV5 $uuidWithString;

    public function __construct()
    {
        $this->uuidWithBytes = new UuidV5("\x0a\xe0\xca\xc5\x2a\x40\x56\x5c\x99\xed\x3d\x33\x1b\x7c\xf7\x2a");
        $this->uuidWithHex = new UuidV5('0ae0cac52a40565c99ed3d331b7cf72a');
        $this->uuidWithString = new UuidV5('0ae0cac5-2a40-565c-99ed-3d331b7cf72a');
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
