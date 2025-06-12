<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Service\Clock;

use PHPUnit\Framework\TestCase;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Service\Clock\GeneratorState;

use function time;

use const PHP_INT_MAX;
use const PHP_INT_MIN;

class GeneratorStateTest extends TestCase
{
    public function testGeneratorStateWithEmptyNode(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('The generator state node must be a non-empty string');

        /** @phpstan-ignore argument.type */
        new GeneratorState(node: '', sequence: 1, timestamp: time());
    }

    public function testGeneratorStateWithNegativeSequence(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('The generator state sequence must be a positive integer');

        /** @phpstan-ignore argument.type */
        new GeneratorState(node: 'a node value', sequence: -1, timestamp: time());
    }

    public function testGeneratorState(): void
    {
        $generatorState = new GeneratorState(node: 'a node value', sequence: PHP_INT_MAX, timestamp: PHP_INT_MIN);

        $this->assertSame('a node value', $generatorState->node);
        $this->assertSame(PHP_INT_MAX, $generatorState->sequence);
        $this->assertSame(PHP_INT_MIN, $generatorState->timestamp);
    }
}
