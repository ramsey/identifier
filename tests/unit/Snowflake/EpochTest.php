<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Snowflake;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Ramsey\Identifier\Snowflake\Epoch;

class EpochTest extends TestCase
{
    #[DataProvider('toIso8601Provider')]
    public function testToIso8601(Epoch $epoch, string $expected): void
    {
        $this->assertSame($expected, $epoch->toIso8601());
    }

    /**
     * @return list<array{epoch: Epoch, expected: string}>
     */
    public static function toIso8601Provider(): array
    {
        return [
            ['epoch' => Epoch::Instagram, 'expected' => '2011-08-24T21:07:01.721Z'],
            ['epoch' => Epoch::Twitter, 'expected' => '2010-11-04T01:42:54.657Z'],
            ['epoch' => Epoch::Discord, 'expected' => '2015-01-01T00:00:00.000Z'],
        ];
    }
}
