<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Uuid;

use Ramsey\Identifier\Uuid\DceDomain;
use Ramsey\Test\Identifier\TestCase;

class DceDomainTest extends TestCase
{
    /**
     * @dataProvider provideEnumCases
     */
    public function testEnumCases(int $value, string $expectedCase): void
    {
        $this->assertSame($expectedCase, DceDomain::from($value)->name);
    }

    /**
     * @return array<array{value: int, expectedCase: string}>
     */
    public function provideEnumCases(): array
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

    /**
     * @dataProvider dceStringNameProvider
     */
    public function testDceStringName(DceDomain $domain, string $expected): void
    {
        $this->assertSame($expected, $domain->dceStringName());
    }

    /**
     * @return array<array{domain: DceDomain, expected: string}>
     */
    public function dceStringNameProvider(): array
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
