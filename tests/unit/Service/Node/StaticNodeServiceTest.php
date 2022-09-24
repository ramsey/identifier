<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Service\Node;

use InvalidArgumentException;
use Ramsey\Identifier\Service\Node\StaticNodeService;
use Ramsey\Test\Identifier\TestCase;

class StaticNodeServiceTest extends TestCase
{
    /**
     * @param int<0, max> | non-empty-string $staticNode
     *
     * @dataProvider nodeProvider
     */
    public function testGetNode(int | string $staticNode, string $expected): void
    {
        $service = new StaticNodeService($staticNode);

        $this->assertSame($expected, $service->getNode());
    }

    /**
     * @return array<array{staticNode: int<0, max> | non-empty-string, expected: string}>
     */
    public function nodeProvider(): array
    {
        return [
            [
                'staticNode' => 0,
                'expected' => '010000000000',
            ],
            [
                'staticNode' => 'ffffffffffff',
                'expected' => 'ffffffffffff',
            ],
            [
                'staticNode' => 'ffff',
                'expected' => '01000000ffff',
            ],
            [
                'staticNode' => 0xffff,
                'expected' => '01000000ffff',
            ],
            [
                'staticNode' => 0xffff0000,
                'expected' => '0100ffff0000',
            ],
            [
                'staticNode' => '000000000000',
                'expected' => '010000000000',
            ],
            [
                'staticNode' => '010000000000',
                'expected' => '010000000000',
            ],
            [
                'staticNode' => '020000000000',
                'expected' => '030000000000',
            ],
            [
                'staticNode' => '030000000000',
                'expected' => '030000000000',
            ],
            [
                'staticNode' => '040000000000',
                'expected' => '050000000000',
            ],
            [
                'staticNode' => '050000000000',
                'expected' => '050000000000',
            ],
            [
                'staticNode' => '060000000000',
                'expected' => '070000000000',
            ],
            [
                'staticNode' => '070000000000',
                'expected' => '070000000000',
            ],
            [
                'staticNode' => '080000000000',
                'expected' => '090000000000',
            ],
            [
                'staticNode' => '090000000000',
                'expected' => '090000000000',
            ],
            [
                'staticNode' => '0a0000000000',
                'expected' => '0b0000000000',
            ],
            [
                'staticNode' => '0b0000000000',
                'expected' => '0b0000000000',
            ],
            [
                'staticNode' => '0c0000000000',
                'expected' => '0d0000000000',
            ],
            [
                'staticNode' => '0d0000000000',
                'expected' => '0d0000000000',
            ],
            [
                'staticNode' => '0e0000000000',
                'expected' => '0f0000000000',
            ],
            [
                'staticNode' => '0f0000000000',
                'expected' => '0f0000000000',
            ],
            [
                'staticNode' => '3c1239b4f540',
                'expected' => '3d1239b4f540',
            ],
            [
                // This test expects a 64-bit build of PHP.
                'staticNode' => 66048975238464,
                'expected' => '3d1239b4f540',
            ],
        ];
    }

    /**
     * @param non-empty-string $value
     *
     * @dataProvider invalidNodeProvider
     */
    public function testGetNodeThrowsException(string $value): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Node must be a 48-bit integer or hexadecimal string');

        new StaticNodeService($value);
    }

    /**
     * @return array<array{value: non-empty-string}>
     */
    public function invalidNodeProvider(): array
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