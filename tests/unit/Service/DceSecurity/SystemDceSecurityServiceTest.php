<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Service\DceSecurity;

use Hamcrest\Type\IsInteger;
use InvalidArgumentException as PhpInvalidArgumentException;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException as CacheInvalidArgumentException;
use Ramsey\Identifier\Exception\DceSecurityIdentifierNotFound;
use Ramsey\Identifier\Exception\InvalidCacheKey;
use Ramsey\Identifier\Service\DceSecurity\SystemDceSecurityService;
use Ramsey\Test\Identifier\TestCase;

use function sprintf;

class SystemDceSecurityServiceTest extends TestCase
{
    public function testGetOrgIdentifier(): void
    {
        $service = new SystemDceSecurityService(orgId: 4001);

        $this->assertSame(4001, $service->getOrgIdentifier());
    }

    public function testGetOrgIdentifierThrowsExceptionForMissingId(): void
    {
        $service = new SystemDceSecurityService();

        $this->expectException(DceSecurityIdentifierNotFound::class);
        $this->expectExceptionMessage(
            'To use the org identifier, you must set $orgId when instantiating ' . SystemDceSecurityService::class,
        );

        $service->getOrgIdentifier();
    }

    /**
     * @runInSeparateProcess since the identifier is stored statically on the class
     * @preserveGlobalState disabled
     */
    public function testGetGroupIdentifier(): void
    {
        $service = new SystemDceSecurityService();

        $this->assertGreaterThanOrEqual(0, $service->getGroupIdentifier());
    }

    /**
     * @runInSeparateProcess since the identifier is stored statically on the class
     * @preserveGlobalState disabled
     */
    public function testGetGroupIdentifierFromCache(): void
    {
        $cache = $this->mockery(CacheInterface::class);
        $cache->expects()->get(SystemDceSecurityService::class . '::$groupId')->andReturns(5001);

        $service = new SystemDceSecurityService(cache: $cache);

        $this->assertSame(5001, $service->getGroupIdentifier());

        // Assert subsequent calls do not attempt to fetch from cache.
        $this->assertSame(5001, $service->getGroupIdentifier());
    }

    /**
     * @runInSeparateProcess since the identifier is stored statically on the class
     * @preserveGlobalState disabled
     */
    public function testGetGroupIdentifierFromCacheThrowsExceptionForBadCacheKey(): void
    {
        $cache = $this->mockery(CacheInterface::class);
        $cache->expects()->get(SystemDceSecurityService::class . '::$groupId')->andThrows(
            new class extends PhpInvalidArgumentException implements CacheInvalidArgumentException {
            },
        );

        $service = new SystemDceSecurityService(cache: $cache);

        $this->expectException(InvalidCacheKey::class);
        $this->expectExceptionMessage(sprintf(
            'A problem occurred when attempting to use the cache key "%s"',
            SystemDceSecurityService::class . '::$groupId',
        ));

        $service->getGroupIdentifier();
    }

    /**
     * @runInSeparateProcess since the identifier is stored statically on the class
     * @preserveGlobalState disabled
     */
    public function testGetGroupIdentifierFromCacheSetsIdentifierOnCache(): void
    {
        $cache = $this->mockery(CacheInterface::class);
        $cache->expects()->get(SystemDceSecurityService::class . '::$groupId')->andReturns(null);
        $cache->expects()->set(SystemDceSecurityService::class . '::$groupId', new IsInteger())->andReturns(true);

        $service = new SystemDceSecurityService(cache: $cache);
        $groupId = $service->getGroupIdentifier();

        $this->assertGreaterThanOrEqual(0, $groupId);

        // Assert subsequent calls do not attempt to fetch from cache.
        $this->assertSame($groupId, $service->getGroupIdentifier());
    }

    /**
     * @runInSeparateProcess since the identifier is stored statically on the class
     * @preserveGlobalState disabled
     */
    public function testGetGroupIdentifierThrowsExceptionWhenIdentifierNotFound(): void
    {
        $cache = $this->mockery(CacheInterface::class);
        $cache->expects()->get(SystemDceSecurityService::class . '::$groupId')->andReturns(-1);

        $service = new SystemDceSecurityService(cache: $cache);

        $this->expectException(DceSecurityIdentifierNotFound::class);
        $this->expectExceptionMessage(
            'Unable to get a group identifier using the system DCE '
            . 'Security service; please provide a custom identifier or use '
            . 'a different DCE service class',
        );

        $service->getGroupIdentifier();
    }

    /**
     * @runInSeparateProcess since the identifier is stored statically on the class
     * @preserveGlobalState disabled
     */
    public function testGetPersonIdentifier(): void
    {
        $service = new SystemDceSecurityService();

        $this->assertGreaterThanOrEqual(0, $service->getPersonIdentifier());
    }

    /**
     * @runInSeparateProcess since the identifier is stored statically on the class
     * @preserveGlobalState disabled
     */
    public function testGetPersonIdentifierFromCache(): void
    {
        $cache = $this->mockery(CacheInterface::class);
        $cache->expects()->get(SystemDceSecurityService::class . '::$personId')->andReturns(6001);

        $service = new SystemDceSecurityService(cache: $cache);

        $this->assertSame(6001, $service->getPersonIdentifier());

        // Assert subsequent calls do not attempt to fetch from cache.
        $this->assertSame(6001, $service->getPersonIdentifier());
    }

    /**
     * @runInSeparateProcess since the identifier is stored statically on the class
     * @preserveGlobalState disabled
     */
    public function testGetPersonIdentifierFromCacheThrowsExceptionForBadCacheKey(): void
    {
        $cache = $this->mockery(CacheInterface::class);
        $cache->expects()->get(SystemDceSecurityService::class . '::$personId')->andThrows(
            new class extends PhpInvalidArgumentException implements CacheInvalidArgumentException {
            },
        );

        $service = new SystemDceSecurityService(cache: $cache);

        $this->expectException(InvalidCacheKey::class);
        $this->expectExceptionMessage(sprintf(
            'A problem occurred when attempting to use the cache key "%s"',
            SystemDceSecurityService::class . '::$personId',
        ));

        $service->getPersonIdentifier();
    }

    /**
     * @runInSeparateProcess since the identifier is stored statically on the class
     * @preserveGlobalState disabled
     */
    public function testGetPersonIdentifierFromCacheSetsIdentifierOnCache(): void
    {
        $cache = $this->mockery(CacheInterface::class);
        $cache->expects()->get(SystemDceSecurityService::class . '::$personId')->andReturns(null);
        $cache->expects()->set(SystemDceSecurityService::class . '::$personId', new IsInteger())->andReturns(true);

        $service = new SystemDceSecurityService(cache: $cache);
        $personId = $service->getPersonIdentifier();

        $this->assertGreaterThanOrEqual(0, $personId);

        // Assert subsequent calls do not attempt to fetch from cache.
        $this->assertSame($personId, $service->getPersonIdentifier());
    }

    /**
     * @runInSeparateProcess since the identifier is stored statically on the class
     * @preserveGlobalState disabled
     */
    public function testGetPersonIdentifierThrowsExceptionWhenIdentifierNotFound(): void
    {
        $cache = $this->mockery(CacheInterface::class);
        $cache->expects()->get(SystemDceSecurityService::class . '::$personId')->andReturns(-1);

        $service = new SystemDceSecurityService(cache: $cache);

        $this->expectException(DceSecurityIdentifierNotFound::class);
        $this->expectExceptionMessage(
            'Unable to get a person identifier using the system DCE '
            . 'Security service; please provide a custom identifier or use '
            . 'a different DCE service class',
        );

        $service->getPersonIdentifier();
    }
}
