<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Uuid;

use PHPUnit\Framework\Attributes\DataProvider;
use Ramsey\Identifier\Uuid\NamespaceId;
use Ramsey\Identifier\Uuid\UuidV1;
use Ramsey\Identifier\Uuid\UuidV5;
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
                'expectedCase' => 'Dns',
            ],
            [
                'value' => '6ba7b812-9dad-11d1-80b4-00c04fd430c8',
                'expectedCase' => 'Oid',
            ],
            [
                'value' => '6ba7b811-9dad-11d1-80b4-00c04fd430c8',
                'expectedCase' => 'Url',
            ],
            [
                'value' => '6ba7b814-9dad-11d1-80b4-00c04fd430c8',
                'expectedCase' => 'X500',
            ],
            [
                'value' => '47fbdabb-f2e4-55f0-bb39-3620c2f6df4e',
                'expectedCase' => 'CborPen',
            ],
        ];
    }

    /**
     * @param class-string $expectedInstance
     */
    #[DataProvider('uuidProvider')]
    public function testUuid(NamespaceId $ns, string $expected, string $expectedInstance): void
    {
        $uuid = $ns->uuid();

        $this->assertInstanceOf($expectedInstance, $uuid);
        $this->assertSame($expected, $uuid->toString());
    }

    /**
     * @return list<array{ns: NamespaceId, expected: string, expectedInstance: class-string}>
     */
    public static function uuidProvider(): array
    {
        return [
            [
                'ns' => NamespaceId::Dns,
                'expected' => '6ba7b810-9dad-11d1-80b4-00c04fd430c8',
                'expectedInstance' => UuidV1::class,
            ],
            [
                'ns' => NamespaceId::Oid,
                'expected' => '6ba7b812-9dad-11d1-80b4-00c04fd430c8',
                'expectedInstance' => UuidV1::class,
            ],
            [
                'ns' => NamespaceId::Url,
                'expected' => '6ba7b811-9dad-11d1-80b4-00c04fd430c8',
                'expectedInstance' => UuidV1::class,
            ],
            [
                'ns' => NamespaceId::X500,
                'expected' => '6ba7b814-9dad-11d1-80b4-00c04fd430c8',
                'expectedInstance' => UuidV1::class,
            ],
            [
                'ns' => NamespaceId::CborPen,
                'expected' => '47fbdabb-f2e4-55f0-bb39-3620c2f6df4e',
                'expectedInstance' => UuidV5::class,
            ],
        ];
    }
}
