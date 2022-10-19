<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Service\Dce;

use Hamcrest\Type\IsInteger;
use InvalidArgumentException as PhpInvalidArgumentException;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException as CacheInvalidArgumentException;
use Ramsey\Identifier\Exception\DceIdentifierNotFound;
use Ramsey\Identifier\Exception\InvalidCacheKey;
use Ramsey\Identifier\Service\Dce\SystemDce;
use Ramsey\Test\Identifier\TestCase;

use function sprintf;

class SystemDceTest extends TestCase
{
    public function testOrgId(): void
    {
        $dce = new SystemDce(orgId: 4001);

        $this->assertSame(4001, $dce->orgId());
    }

    public function testOrgIdThrowsExceptionForMissingId(): void
    {
        $dce = new SystemDce();

        $this->expectException(DceIdentifierNotFound::class);
        $this->expectExceptionMessage(
            'To use the org identifier, you must set $orgId when instantiating ' . SystemDce::class,
        );

        $dce->orgId();
    }

    /**
     * @runInSeparateProcess since the identifier is stored statically on the class
     * @preserveGlobalState disabled
     */
    public function testGroupId(): void
    {
        $dce = new SystemDce();

        $this->assertGreaterThanOrEqual(0, $dce->groupId());
    }

    /**
     * @runInSeparateProcess since the identifier is stored statically on the class
     * @preserveGlobalState disabled
     */
    public function testGroupIdFromCache(): void
    {
        $cache = $this->mockery(CacheInterface::class);
        $cache->expects()->get(SystemDce::class . '::$groupId')->andReturns(5001);

        $dce = new SystemDce(cache: $cache);

        $this->assertSame(5001, $dce->groupId());

        // Assert subsequent calls do not attempt to fetch from cache.
        $this->assertSame(5001, $dce->groupId());
    }

    /**
     * @runInSeparateProcess since the identifier is stored statically on the class
     * @preserveGlobalState disabled
     */
    public function testGroupIdFromCacheThrowsExceptionForBadCacheKey(): void
    {
        $cache = $this->mockery(CacheInterface::class);
        $cache->expects()->get(SystemDce::class . '::$groupId')->andThrows(
            new class extends PhpInvalidArgumentException implements CacheInvalidArgumentException {
            },
        );

        $dce = new SystemDce(cache: $cache);

        $this->expectException(InvalidCacheKey::class);
        $this->expectExceptionMessage(sprintf(
            'A problem occurred when attempting to use the cache key "%s"',
            SystemDce::class . '::$groupId',
        ));

        $dce->groupId();
    }

    /**
     * @runInSeparateProcess since the identifier is stored statically on the class
     * @preserveGlobalState disabled
     */
    public function testGroupIdFromCacheSetsIdentifierOnCache(): void
    {
        $cache = $this->mockery(CacheInterface::class);
        $cache->expects()->get(SystemDce::class . '::$groupId')->andReturns(null);
        $cache->expects()->set(SystemDce::class . '::$groupId', new IsInteger())->andReturns(true);

        $dce = new SystemDce(cache: $cache);
        $groupId = $dce->groupId();

        $this->assertGreaterThanOrEqual(0, $groupId);

        // Assert subsequent calls do not attempt to fetch from cache.
        $this->assertSame($groupId, $dce->groupId());
    }

    /**
     * @runInSeparateProcess since the identifier is stored statically on the class
     * @preserveGlobalState disabled
     */
    public function testGroupIdThrowsExceptionWhenIdentifierNotFound(): void
    {
        $cache = $this->mockery(CacheInterface::class);
        $cache->expects()->get(SystemDce::class . '::$groupId')->andReturns(-1);

        $dce = new SystemDce(cache: $cache);

        $this->expectException(DceIdentifierNotFound::class);
        $this->expectExceptionMessage(
            'Unable to get a group identifier using the system DCE '
            . 'service; please provide a custom identifier or use '
            . 'a different DCE service class',
        );

        $dce->groupId();
    }

    /**
     * @runInSeparateProcess since the identifier is stored statically on the class
     * @preserveGlobalState disabled
     */
    public function testUserId(): void
    {
        $dce = new SystemDce();

        $this->assertGreaterThanOrEqual(0, $dce->userId());
    }

    /**
     * @runInSeparateProcess since the identifier is stored statically on the class
     * @preserveGlobalState disabled
     */
    public function testUserIdFromCache(): void
    {
        $cache = $this->mockery(CacheInterface::class);
        $cache->expects()->get(SystemDce::class . '::$userId')->andReturns(6001);

        $dce = new SystemDce(cache: $cache);

        $this->assertSame(6001, $dce->userId());

        // Assert subsequent calls do not attempt to fetch from cache.
        $this->assertSame(6001, $dce->userId());
    }

    /**
     * @runInSeparateProcess since the identifier is stored statically on the class
     * @preserveGlobalState disabled
     */
    public function testUserIdFromCacheThrowsExceptionForBadCacheKey(): void
    {
        $cache = $this->mockery(CacheInterface::class);
        $cache->expects()->get(SystemDce::class . '::$userId')->andThrows(
            new class extends PhpInvalidArgumentException implements CacheInvalidArgumentException {
            },
        );

        $dce = new SystemDce(cache: $cache);

        $this->expectException(InvalidCacheKey::class);
        $this->expectExceptionMessage(sprintf(
            'A problem occurred when attempting to use the cache key "%s"',
            SystemDce::class . '::$userId',
        ));

        $dce->userId();
    }

    /**
     * @runInSeparateProcess since the identifier is stored statically on the class
     * @preserveGlobalState disabled
     */
    public function testUserIdFromCacheSetsIdentifierOnCache(): void
    {
        $cache = $this->mockery(CacheInterface::class);
        $cache->expects()->get(SystemDce::class . '::$userId')->andReturns(null);
        $cache->expects()->set(SystemDce::class . '::$userId', new IsInteger())->andReturns(true);

        $dce = new SystemDce(cache: $cache);
        $userId = $dce->userId();

        $this->assertGreaterThanOrEqual(0, $userId);

        // Assert subsequent calls do not attempt to fetch from cache.
        $this->assertSame($userId, $dce->userId());
    }

    /**
     * @runInSeparateProcess since the identifier is stored statically on the class
     * @preserveGlobalState disabled
     */
    public function testUserIdThrowsExceptionWhenIdentifierNotFound(): void
    {
        $cache = $this->mockery(CacheInterface::class);
        $cache->expects()->get(SystemDce::class . '::$userId')->andReturns(-1);

        $dce = new SystemDce(cache: $cache);

        $this->expectException(DceIdentifierNotFound::class);
        $this->expectExceptionMessage(
            'Unable to get a user identifier using the system DCE '
            . 'service; please provide a custom identifier or use '
            . 'a different DCE service class',
        );

        $dce->userId();
    }
}
