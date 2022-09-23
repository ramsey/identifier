<?php

declare(strict_types=1);

namespace Ramsey\Benchmark\Identifier\Uuid;

use Identifier\Uuid\UuidInterface;
use Ramsey\Identifier\Uuid\UuidV4;

final class UuidToVariousFormatsBench
{
    private UuidInterface $uuidWithBytes;
    private UuidInterface $uuidWithHex;
    private UuidInterface $uuidWithString;

    public function __construct()
    {
        $this->uuidWithBytes = new UuidV4('0ae0cac52a40465c99ed3d331b7cf72a');
        $this->uuidWithHex = new UuidV4('0ae0cac52a40465c99ed3d331b7cf72a');
        $this->uuidWithString = new UuidV4('0ae0cac5-2a40-465c-99ed-3d331b7cf72a');
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
