<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Service\Nic;

use Mockery;
use Psr\SimpleCache\CacheInterface;
use Ramsey\Identifier\Service\Nic\RandomNic;
use Ramsey\Test\Identifier\TestCase;

use function hexdec;
use function strlen;
use function substr;

class RandomNicTest extends TestCase
{
    /**
     * @runInSeparateProcess since the address is stored statically on the class
     * @preserveGlobalState disabled
     */
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

    /**
     * @runInSeparateProcess since the address is stored statically on the class
     * @preserveGlobalState disabled
     */
    public function testAddressFoundInCache(): void
    {
        $cache = $this->mockery(CacheInterface::class);
        $cache->expects('get')->with('__ramsey_id_random_addr')->andReturn('aabbccddeeff');

        $nic = new RandomNic($cache);

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

    /**
     * @runInSeparateProcess since the address is stored statically on the class
     * @preserveGlobalState disabled
     */
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
}
