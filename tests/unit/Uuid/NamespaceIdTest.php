<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Uuid;

use PHPUnit\Framework\Attributes\DataProvider;
use Ramsey\Identifier\Uuid\NamespaceId;
use Ramsey\Identifier\Uuid\UuidV1;
use Ramsey\Test\Identifier\TestCase;

class NamespaceIdTest extends TestCase
{
    #[DataProvider('provideEnumCases')]
    public function testEnumCases(string $value, string $expectedCase): void
    {
        $this->assertSame($expectedCase, NamespaceId::from($value)->name);
    }

    /**
     * @return list<array{value: string, expectedCase: string}>
     */
    public static function provideEnumCases(): array
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

    #[DataProvider('uuidProvider')]
    public function testUuid(NamespaceId $ns, string $expected): void
    {
        $uuid = $ns->uuid();

        $this->assertInstanceOf(UuidV1::class, $uuid);
        $this->assertSame($expected, $uuid->toString());
    }

    /**
     * @return list<array{ns: NamespaceId, expected: string}>
     */
    public static function uuidProvider(): array
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
