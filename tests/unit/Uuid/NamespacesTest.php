<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Uuid;

use Ramsey\Identifier\Uuid\Namespaces;
use Ramsey\Identifier\Uuid\UuidV1;
use Ramsey\Test\Identifier\TestCase;

class NamespacesTest extends TestCase
{
    /**
     * @dataProvider provideEnumCases
     */
    public function testEnumCases(string $value, string $expectedCase): void
    {
        $this->assertSame($expectedCase, Namespaces::from($value)->name);
    }

    /**
     * @return array<array{value: string, expectedCase: string}>
     */
    public function provideEnumCases(): array
    {
        return [
            [
                'value' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                'expectedCase' => 'DNS',
            ],
            [
                'value' => '6ba7b812-9dad-11d1-80b4-00c04fd430c8',
                'expectedCase' => 'OID',
            ],
            [
                'value' => '6ba7b811-9dad-11d1-80b4-00c04fd430c8',
                'expectedCase' => 'URL',
            ],
            [
                'value' => '6ba7b814-9dad-11d1-80b4-00c04fd430c8',
                'expectedCase' => 'X500',
            ],
        ];
    }

    /**
     * @dataProvider uuidProvider
     */
    public function testUuid(Namespaces $ns, string $expected): void
    {
        $uuid = $ns->uuid();

        $this->assertInstanceOf(UuidV1::class, $uuid);
        $this->assertSame($expected, $uuid->toString());
    }

    /**
     * @return array<array{ns: Namespaces, expected: string}>
     */
    public function uuidProvider(): array
    {
        return [
            [
                'ns' => Namespaces::DNS,
                'expected' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            ],
            [
                'ns' => Namespaces::OID,
                'expected' => '6ba7b812-9dad-11d1-80b4-00c04fd430c8',
            ],
            [
                'ns' => Namespaces::URL,
                'expected' => '6ba7b811-9dad-11d1-80b4-00c04fd430c8',
            ],
            [
                'ns' => Namespaces::X500,
                'expected' => '6ba7b814-9dad-11d1-80b4-00c04fd430c8',
            ],
        ];
    }
}
