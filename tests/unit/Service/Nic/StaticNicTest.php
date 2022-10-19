<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Service\Nic;

use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Service\Nic\StaticNic;
use Ramsey\Test\Identifier\TestCase;

use const PHP_INT_SIZE;

class StaticNicTest extends TestCase
{
    /**
     * @param int<0, max> | non-empty-string $staticAddress
     *
     * @dataProvider addressProvider
     */
    public function testAddress(int | string $staticAddress, string $expected): void
    {
        $nic = new StaticNic($staticAddress);

        $this->assertSame($expected, $nic->address());
    }

    /**
     * @return array<array{staticAddress: int<0, max> | non-empty-string, expected: string}>
     */
    public function addressProvider(): array
    {
        return [
            [
                'staticAddress' => 0,
                'expected' => '010000000000',
            ],
            [
                'staticAddress' => 'ffffffffffff',
                'expected' => 'ffffffffffff',
            ],
            [
                'staticAddress' => 'ffff',
                'expected' => '01000000ffff',
            ],
            [
                'staticAddress' => 0xffff,
                'expected' => '01000000ffff',
            ],
            [
                'staticAddress' => 0x7fff0000,
                'expected' => '01007fff0000',
            ],
            [
                'staticAddress' => '000000000000',
                'expected' => '010000000000',
            ],
            [
                'staticAddress' => '010000000000',
                'expected' => '010000000000',
            ],
            [
                'staticAddress' => '020000000000',
                'expected' => '030000000000',
            ],
            [
                'staticAddress' => '030000000000',
                'expected' => '030000000000',
            ],
            [
                'staticAddress' => '040000000000',
                'expected' => '050000000000',
            ],
            [
                'staticAddress' => '050000000000',
                'expected' => '050000000000',
            ],
            [
                'staticAddress' => '060000000000',
                'expected' => '070000000000',
            ],
            [
                'staticAddress' => '070000000000',
                'expected' => '070000000000',
            ],
            [
                'staticAddress' => '080000000000',
                'expected' => '090000000000',
            ],
            [
                'staticAddress' => '090000000000',
                'expected' => '090000000000',
            ],
            [
                'staticAddress' => '0a0000000000',
                'expected' => '0b0000000000',
            ],
            [
                'staticAddress' => '0b0000000000',
                'expected' => '0b0000000000',
            ],
            [
                'staticAddress' => '0c0000000000',
                'expected' => '0d0000000000',
            ],
            [
                'staticAddress' => '0d0000000000',
                'expected' => '0d0000000000',
            ],
            [
                'staticAddress' => '0e0000000000',
                'expected' => '0f0000000000',
            ],
            [
                'staticAddress' => '0f0000000000',
                'expected' => '0f0000000000',
            ],
            [
                'staticAddress' => '3c1239b4f540',
                'expected' => '3d1239b4f540',
            ],
            [
                'staticAddress' => 0x7fffffff,
                'expected' => '01007fffffff',
            ],
        ];
    }

    public function testGetAddress64Bit(): void
    {
        if (PHP_INT_SIZE < 8) {
            $this->markTestSkipped('Skipping on 32-bit build of PHP');
        }

        $nic1 = new StaticNic(0xffff0000);
        $nic2 = new StaticNic(66048975238464);

        $this->assertSame('0100ffff0000', $nic1->address());
        $this->assertSame('3d1239b4f540', $nic2->address());
    }

    /**
     * @param non-empty-string $value
     *
     * @dataProvider invalidAddressProvider
     */
    public function testGetAddressThrowsException(string $value): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Address must be a 48-bit integer or hexadecimal string');

        new StaticNic($value);
    }

    /**
     * @return array<array{value: non-empty-string}>
     */
    public function invalidAddressProvider(): array
    {
        return [
            [
                'value' => 'foobar',
            ],
            [
                'value' => 'fffffffffffff',
            ],
        ];
    }
}
