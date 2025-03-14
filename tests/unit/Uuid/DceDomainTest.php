<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Uuid;

use PHPUnit\Framework\Attributes\DataProvider;
use Ramsey\Identifier\Uuid\DceDomain;
use Ramsey\Test\Identifier\TestCase;

class DceDomainTest extends TestCase
{
    #[DataProvider('provideEnumCases')]
    public function testEnumCases(int $value, string $expectedCase): void
    {
        $this->assertSame($expectedCase, DceDomain::from($value)->name);
    }

    /**
     * @return list<array{value: int, expectedCase: string}>
     */
    public static function provideEnumCases(): array
    {
        return [
            [
                'value' => 0,
                'expectedCase' => 'Person',
            ],
            [
                'value' => 1,
                'expectedCase' => 'Group',
            ],
            [
                'value' => 2,
                'expectedCase' => 'Org',
            ],
        ];
    }

    #[DataProvider('dceStringNameProvider')]
    public function testDceStringName(DceDomain $domain, string $expected): void
    {
        $this->assertSame($expected, $domain->dceStringName());
    }

    /**
     * @return list<array{domain: DceDomain, expected: string}>
     */
    public static function dceStringNameProvider(): array
    {
        return [
            [
                'domain' => DceDomain::Person,
                'expected' => 'person',
            ],
            [
                'domain' => DceDomain::Group,
                'expected' => 'group',
            ],
            [
                'domain' => DceDomain::Org,
                'expected' => 'org',
            ],
        ];
    }
}
