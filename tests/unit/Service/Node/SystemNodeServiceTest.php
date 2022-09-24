<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Service\Node;

use InvalidArgumentException as PhpInvalidArgumentException;
use Mockery;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException as CacheInvalidArgumentException;
use Ramsey\Identifier\Exception\CacheException;
use Ramsey\Identifier\Exception\NodeNotFoundException;
use Ramsey\Identifier\Service\Node\SystemNodeService;
use Ramsey\Identifier\Util;
use Ramsey\Test\Identifier\TestCase;

use function hexdec;
use function sprintf;
use function strlen;
use function strspn;
use function substr;

class SystemNodeServiceTest extends TestCase
{
    /**
     * @runInSeparateProcess since the node is stored statically on the class
     * @preserveGlobalState disabled
     */
    public function testGetNode(): void
    {
        $service = new SystemNodeService();
        $node = $service->getNode();
        $firstOctet = substr($node, 0, 2);

        $this->assertSame(12, strlen($node));

        // Assert the multicast bit is not set.
        $this->assertSame(0, hexdec($firstOctet) & 0x01);
    }

    /**
     * @runInSeparateProcess since the node is stored statically on the class
     * @preserveGlobalState disabled
     */
    public function testGetNodeFoundInCache(): void
    {
        $cache = $this->mockery(CacheInterface::class);
        $cache->expects()->get(SystemNodeService::class . '::$node')->andReturns('aabbccddeeff');

        $service = new SystemNodeService($cache);

        $this->assertSame('aabbccddeeff', $service->getNode());

        // This second assertion tests that the cache is not accessed again.
        $this->assertSame('aabbccddeeff', $service->getNode());
    }

    /**
     * @runInSeparateProcess since the node is stored statically on the class
     * @preserveGlobalState disabled
     */
    public function testGetNodeStoredInCache(): void
    {
        $cache = $this->mockery(CacheInterface::class);
        $cache->expects()->get(SystemNodeService::class . '::$node')->andReturns(null);
        $cache
            ->expects()
            ->set(
                SystemNodeService::class . '::$node',
                Mockery::on(fn (string $value): bool => strspn($value, Util::HEX_MASK) === 12),
            )
            ->andReturns(true);

        $service = new SystemNodeService($cache);
        $node = $service->getNode();

        $this->assertSame(12, strlen($node));

        // This second assertion tests that the cache get() and set() methods
        // are not called again, and that we still get the same system node.
        $this->assertSame($node, $service->getNode());
    }

    /**
     * @runInSeparateProcess since the node is stored statically on the class
     * @preserveGlobalState disabled
     */
    public function testGetNodeFromCacheThrowsCacheException(): void
    {
        $cache = $this->mockery(CacheInterface::class);
        $cache->expects()->get(SystemNodeService::class . '::$node')->andThrows(
            new class extends PhpInvalidArgumentException implements CacheInvalidArgumentException {
            },
        );

        $service = new SystemNodeService($cache);

        $this->expectException(CacheException::class);
        $this->expectExceptionMessage(sprintf(
            'A problem occurred when attempting to use the cache key "%s"',
            SystemNodeService::class . '::$node',
        ));

        $service->getNode();
    }

    /**
     * @runInSeparateProcess since the node is stored statically on the class
     * @preserveGlobalState disabled
     */
    public function testGetNodeThrowsExceptionForEmptyStringNode(): void
    {
        $cache = $this->mockery(CacheInterface::class);
        $cache->expects()->get(SystemNodeService::class . '::$node')->andReturns('');

        $service = new SystemNodeService($cache);

        $this->expectException(NodeNotFoundException::class);
        $this->expectExceptionMessage('Unable to fetch a node for this system');

        $service->getNode();
    }
}
