<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

/**
 * A base test case for common test functionality
 */
abstract class TestCase extends PHPUnitTestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * Configures and returns a mock object
     *
     * @param class-string<T> $class
     *
     * @return T & MockInterface
     *
     * @template T
     */
    public function mockery(string $class, mixed ...$arguments): MockInterface
    {
        /** @var T & MockInterface */
        return Mockery::mock($class, ...$arguments);
    }
}
