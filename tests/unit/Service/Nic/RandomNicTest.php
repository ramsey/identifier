<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Service\Nic;

use Exception;
use Mockery;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Psr\SimpleCache\CacheException;
use Psr\SimpleCache\CacheInterface;
use Ramsey\Identifier\Exception\MacAddressNotFound;
use Ramsey\Identifier\Service\Nic\RandomNic;
use Ramsey\Test\Identifier\TestCase;

use function hexdec;
use function strlen;
use function substr;

class RandomNicTest extends TestCase
{
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testAddress(): void
    {
        $nic = new RandomNic();
        $address = $nic->address();
        $firstOctet = substr($address, 0, 2);

        $this->assertSame(12, strlen($address));

        // Assert the multicast bit is set.
        $this->assertSame(1, hexdec($firstOctet) & 0x01);

        // Assert the address is the same for subsequent calls.
        $this->assertSame($address, $nic->address());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testAddressFoundInCache(): void
    {
        $cache = $this->mockery(CacheInterface::class);
        $cache->expects('get')->with('__ramsey_id_random_addr')->andReturn('aabbccddeeff');

        $nic = new RandomNic($cache);

        $this->assertSame('aabbccddeeff', $nic->address());

        // This second assertion tests that the cache is not accessed again.
        $this->assertSame('aabbccddeeff', $nic->address());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testAddressStoredInCache(): void
    {
        $cache = $this->mockery(CacheInterface::class);
        $cache->expects('get')->with('__ramsey_id_random_addr')->andReturnNull();
        $cache
            ->expects('set')
            ->with('__ramsey_id_random_addr', Mockery::pattern('/^[0-9a-f]{12}$/i'))
            ->andReturnTrue();

        $nic = new RandomNic($cache);
        $address = $nic->address();

        $this->assertSame(12, strlen($address));

        // This second assertion tests that the cache get() and set() methods
        // are not called again, and that we still get the same address.
        $this->assertSame($address, $nic->address());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testAddressStoredInCacheIsSomehowAnEmptyString(): void
    {
        $cache = $this->mockery(CacheInterface::class);
        $cache->expects('get')->with('__ramsey_id_random_addr')->andReturn('');
        $cache
            ->expects('set')
            ->with('__ramsey_id_random_addr', Mockery::pattern('/^[0-9a-f]{12}$/i'))
            ->andReturnTrue();

        $nic = new RandomNic($cache);
        $address = $nic->address();

        $this->assertSame(12, strlen($address));

        // This second assertion tests that the cache get() and set() methods
        // are not called again, and that we still get the same address.
        $this->assertSame($address, $nic->address());
    }

    public function testAddressThrowsExceptionFromCache(): void
    {
        $exception = new class extends Exception implements CacheException {
        };

        $cache = $this->mockery(CacheInterface::class);
        $cache->expects('get')->with('__ramsey_id_random_addr')->andThrow($exception);

        $nic = new RandomNic($cache);

        $this->expectException(MacAddressNotFound::class);
        $this->expectExceptionMessage('Unable to retrieve MAC address from cache');

        $nic->address();
    }
}
