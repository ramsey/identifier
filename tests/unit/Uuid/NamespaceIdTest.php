<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Uuid;

use Ramsey\Identifier\Uuid\NamespaceId;
use Ramsey\Identifier\Uuid\UuidV1;
use Ramsey\Test\Identifier\TestCase;

class NamespaceIdTest extends TestCase
{
    /**
     * @dataProvider provideEnumCases
     */
    public function testEnumCases(string $value, string $expectedCase): void
    {
        $this->assertSame($expectedCase, NamespaceId::from($value)->name);
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
    public function testUuid(NamespaceId $ns, string $expected): void
    {
        $uuid = $ns->uuid();

        $this->assertInstanceOf(UuidV1::class, $uuid);
        $this->assertSame($expected, $uuid->toString());
    }

    /**
     * @return array<array{ns: NamespaceId, expected: string}>
     */
    public function uuidProvider(): array
    {
        return [
            [
                'ns' => NamespaceId::DNS,
                'expected' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
            ],
            [
                'ns' => NamespaceId::OID,
                'expected' => '6ba7b812-9dad-11d1-80b4-00c04fd430c8',
            ],
            [
                'ns' => NamespaceId::URL,
                'expected' => '6ba7b811-9dad-11d1-80b4-00c04fd430c8',
            ],
            [
                'ns' => NamespaceId::X500,
                'expected' => '6ba7b814-9dad-11d1-80b4-00c04fd430c8',
            ],
        ];
    }
}
