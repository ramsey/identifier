<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Service\Hash;

use PHPUnit\Framework\TestCase;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Service\Hash\FixedHash;

class FixedHashTest extends TestCase
{
    public function testHash(): void
    {
        $hash = new FixedHash('ffffffffffffffffffffffffffffffff');

        $this->assertSame(
            'ffffffffffffffffffffffffffffffff',
            $hash->hash('some data'),
        );

        $this->assertSame(
            "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff",
            $hash->hash('some other data', true),
        );
    }

    public function testHashThrowsForUnevenLength(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage(
            'The hash value "fffffffffffffffffffffffffffffff" must be a non-empty '
            . 'hexadecimal string with an even length',
        );

        // This has 31 characters instead of 32.
        new FixedHash('fffffffffffffffffffffffffffffff');
    }

    public function testHashThrowsForNonHexadecimalValue(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage(
            'The hash value "foobar" must be a non-empty hexadecimal string with an even length',
        );

        new FixedHash('foobar');
    }
}
