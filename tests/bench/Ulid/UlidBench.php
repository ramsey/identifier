<?php

declare(strict_types=1);

namespace Ramsey\Bench\Identifier\Ulid;

use Ramsey\Identifier\Ulid\Ulid;

final class UlidBench
{
    private Ulid $ulidWithBytes;
    private Ulid $ulidWithHex;
    private Ulid $ulidWithString;

    public function __construct()
    {
        $this->ulidWithBytes = new Ulid("\x0a\xe0\xca\xc5\x2a\x40\x76\x5c\x99\xed\x3d\x33\x1b\x7c\xf7\x2a");
        $this->ulidWithHex = new Ulid('0ae0cac52a40765c99ed3d331b7cf72a');
        $this->ulidWithString = new Ulid('0AW35CAAJ0ESE9KV9X6CDQSXSA');
    }

    public function benchGetDateTimeForBytesUlid(): void
    {
        $this->ulidWithBytes->getDateTime();
    }

    public function benchGetDateTimeForHexUlid(): void
    {
        $this->ulidWithHex->getDateTime();
    }

    public function benchGetDateTimeForStringUlid(): void
    {
        $this->ulidWithString->getDateTime();
    }

    public function benchToBytesForBytesUlid(): void
    {
        $this->ulidWithBytes->toBytes();
    }

    public function benchToBytesForHexUlid(): void
    {
        $this->ulidWithHex->toBytes();
    }

    public function benchToBytesForStringUlid(): void
    {
        $this->ulidWithString->toBytes();
    }

    public function benchToHexadecimalForBytesUlid(): void
    {
        $this->ulidWithBytes->toHexadecimal();
    }

    public function benchToHexadecimalForHexUlid(): void
    {
        $this->ulidWithHex->toHexadecimal();
    }

    public function benchToHexadecimalForStringUlid(): void
    {
        $this->ulidWithString->toHexadecimal();
    }

    public function benchToIntegerForBytesUlid(): void
    {
        $this->ulidWithBytes->toInteger();
    }

    public function benchToIntegerForHexUlid(): void
    {
        $this->ulidWithHex->toInteger();
    }

    public function benchToIntegerForStringUlid(): void
    {
        $this->ulidWithString->toInteger();
    }

    public function benchToStringForBytesUlid(): void
    {
        $this->ulidWithBytes->toString();
    }

    public function benchToStringForHexUlid(): void
    {
        $this->ulidWithHex->toString();
    }

    public function benchToStringForStringUlid(): void
    {
        $this->ulidWithString->toString();
    }
}
