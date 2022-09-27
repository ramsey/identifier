<?php

declare(strict_types=1);

namespace Ramsey\Bench\Identifier\Uuid;

use Ramsey\Identifier\Uuid\UuidV8;

final class UuidV8Bench
{
    private UuidV8 $uuidWithBytes;
    private UuidV8 $uuidWithHex;
    private UuidV8 $uuidWithString;

    public function __construct()
    {
        $this->uuidWithBytes = new UuidV8("\x0a\xe0\xca\xc5\x2a\x40\x86\x5c\x99\xed\x3d\x33\x1b\x7c\xf7\x2a");
        $this->uuidWithHex = new UuidV8('0ae0cac52a40865c99ed3d331b7cf72a');
        $this->uuidWithString = new UuidV8('0ae0cac5-2a40-865c-99ed-3d331b7cf72a');
    }

    public function benchGetCustomFieldAForBytesUuid(): void
    {
        $this->uuidWithBytes->getCustomFieldA();
    }

    public function benchGetCustomFieldAForHexUuid(): void
    {
        $this->uuidWithHex->getCustomFieldA();
    }

    public function benchGetCustomFieldAStringUuid(): void
    {
        $this->uuidWithString->getCustomFieldA();
    }

    public function benchGetCustomFieldBForBytesUuid(): void
    {
        $this->uuidWithBytes->getCustomFieldB();
    }

    public function benchGetCustomFieldBForHexUuid(): void
    {
        $this->uuidWithHex->getCustomFieldB();
    }

    public function benchGetCustomFieldBStringUuid(): void
    {
        $this->uuidWithString->getCustomFieldB();
    }

    public function benchGetCustomFieldCForBytesUuid(): void
    {
        $this->uuidWithBytes->getCustomFieldC();
    }

    public function benchGetCustomFieldCForHexUuid(): void
    {
        $this->uuidWithHex->getCustomFieldC();
    }

    public function benchGetCustomFieldCStringUuid(): void
    {
        $this->uuidWithString->getCustomFieldC();
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
