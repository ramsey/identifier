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
                'value' => "\x6b\xa7\xb8\x10\x9d\xad\x11\xd1\x80\xb4\x00\xc0\x4f\xd4\x30\xc8",
                'expectedCase' => 'Dns',
            ],
            [
                'value' => "\x6b\xa7\xb8\x12\x9d\xad\x11\xd1\x80\xb4\x00\xc0\x4f\xd4\x30\xc8",
                'expectedCase' => 'Oid',
            ],
            [
                'value' => "\x6b\xa7\xb8\x11\x9d\xad\x11\xd1\x80\xb4\x00\xc0\x4f\xd4\x30\xc8",
                'expectedCase' => 'Url',
            ],
            [
                'value' => "\x6b\xa7\xb8\x14\x9d\xad\x11\xd1\x80\xb4\x00\xc0\x4f\xd4\x30\xc8",
                'expectedCase' => 'X500',
            ],
            [
                'value' => "\x47\xfb\xda\xbb\xf2\xe4\x55\xf0\xbb\x39\x36\x20\xc2\xf6\xdf\x4e",
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
