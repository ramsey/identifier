<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Service\Nic;

use InvalidArgumentException as PhpInvalidArgumentException;
use Mockery;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException as CacheInvalidArgumentException;
use Ramsey\Identifier\Exception\InvalidCacheKey;
use Ramsey\Identifier\Exception\MacAddressNotFound;
use Ramsey\Identifier\Service\Nic\SystemNic;
use Ramsey\Identifier\Uuid\Utility\Format;
use Ramsey\Test\Identifier\TestCase;

use function hexdec;
use function sprintf;
use function strlen;
use function strspn;
use function substr;

class SystemNicTest extends TestCase
{
    /**
     * @runInSeparateProcess since the address is stored statically on the class
     * @preserveGlobalState disabled
     */
    public function testAddress(): void
    {
        $nic = new SystemNic();
        $address = $nic->address();
        $firstOctet = substr($address, 0, 2);

        $this->assertSame(12, strlen($address));

        // Assert the multicast bit is not set.
        $this->assertSame(0, hexdec($firstOctet) & 0x01);
    }

    /**
     * @runInSeparateProcess since the address is stored statically on the class
     * @preserveGlobalState disabled
     */
    public function testAddressFoundInCache(): void
    {
        $cache = $this->mockery(CacheInterface::class);
        $cache->expects()->get(SystemNic::class . '::$address')->andReturns('aabbccddeeff');

        $nic = new SystemNic($cache);

        $this->assertSame('aabbccddeeff', $nic->address());

        // This second assertion tests that the cache is not accessed again.
        $this->assertSame('aabbccddeeff', $nic->address());
    }

    /**
     * @runInSeparateProcess since the address is stored statically on the class
     * @preserveGlobalState disabled
     */
    public function testAddressStoredInCache(): void
    {
        $cache = $this->mockery(CacheInterface::class);
        $cache->expects()->get(SystemNic::class . '::$address')->andReturns(null);
        $cache
            ->expects()
            ->set(
                SystemNic::class . '::$address',
                Mockery::on(fn (string $value): bool => strspn($value, Format::MASK_HEX) === 12),
            )
            ->andReturns(true);

        $nic = new SystemNic($cache);
        $address = $nic->address();

        $this->assertSame(12, strlen($address));

        // This second assertion tests that the cache get() and set() methods
        // are not called again, and that we still get the same address.
        $this->assertSame($address, $nic->address());
    }

    /**
     * @runInSeparateProcess since the address is stored statically on the class
     * @preserveGlobalState disabled
     */
    public function testAddressFromCacheThrowsCacheException(): void
    {
        $cache = $this->mockery(CacheInterface::class);
        $cache->expects()->get(SystemNic::class . '::$address')->andThrows(
            new class extends PhpInvalidArgumentException implements CacheInvalidArgumentException {
            },
        );

        $nic = new SystemNic($cache);

        $this->expectException(InvalidCacheKey::class);
        $this->expectExceptionMessage(sprintf(
            'A problem occurred when attempting to use the cache key "%s"',
            SystemNic::class . '::$address',
        ));

        $nic->address();
    }

    /**
     * @runInSeparateProcess since the address is stored statically on the class
     * @preserveGlobalState disabled
     */
    public function testAddressThrowsExceptionForEmptyStringAddress(): void
    {
        $cache = $this->mockery(CacheInterface::class);
        $cache->expects()->get(SystemNic::class . '::$address')->andReturns('');

        $nic = new SystemNic($cache);

        $this->expectException(MacAddressNotFound::class);
        $this->expectExceptionMessage('Unable to fetch an address for this system');

        $nic->address();
    }
}
