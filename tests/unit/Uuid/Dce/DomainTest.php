<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Uuid\Dce;

use Ramsey\Identifier\Uuid\Dce\Domain;
use Ramsey\Test\Identifier\TestCase;

class DomainTest extends TestCase
{
    /**
     * @dataProvider provideEnumCases
     */
    public function testEnumCases(int $value, string $expectedCase): void
    {
        $this->assertSame($expectedCase, Domain::from($value)->name);
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
    public function testDceStringName(Domain $domain, string $expected): void
    {
        $this->assertSame($expected, $domain->dceStringName());
    }

    /**
     * @return array<array{domain: Domain, expected: string}>
     */
    public function dceStringNameProvider(): array
    {
        return [
            [
                'domain' => Domain::Person,
                'expected' => 'person',
            ],
            [
                'domain' => Domain::Group,
                'expected' => 'group',
            ],
            [
                'domain' => Domain::Org,
                'expected' => 'org',
            ],
        ];
    }
}
