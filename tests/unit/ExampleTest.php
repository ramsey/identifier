<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier;

use Mockery\MockInterface;
use Ramsey\Identifier\Example;

class ExampleTest extends TestCase
{
    public function testGreet(): void
    {
        /** @var Example & MockInterface $example */
        $example = $this->mockery(Example::class);
        $example->shouldReceive('greet')->passthru();

        $this->assertSame('Hello, Friends!', $example->greet('Friends'));
    }
}
