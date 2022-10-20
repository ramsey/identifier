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
use Ramsey\Identifier\Service\Os\Os;
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
        $cache->expects('get')->with(SystemNic::class . '::$address')->andReturn('aabbccddeeff');

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
        $cache->expects('get')->with(SystemNic::class . '::$address')->andReturnNull();
        $cache
            ->expects('set')
            ->with(
                SystemNic::class . '::$address',
                Mockery::on(fn (string $value): bool => strspn($value, Format::MASK_HEX) === 12),
            )
            ->andReturnTrue();

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
        $cache->expects('get')->with(SystemNic::class . '::$address')->andThrows(
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
        $cache->expects('get')->with(SystemNic::class . '::$address')->andReturn('');

        $nic = new SystemNic($cache);

        $this->expectException(MacAddressNotFound::class);
        $this->expectExceptionMessage('Unable to fetch an address for this system');

        $nic->address();
    }

    /**
     * @runInSeparateProcess since the address is stored statically on the class
     * @preserveGlobalState disabled
     */
    public function testAddressOnWindows(): void
    {
        $ipconfig = <<<'IPCONFIG'
            Windows IP Configuration
               Host Name . . . . . . . . . . . . : MSEDGEWIN10
               Primary Dns Suffix  . . . . . . . :
               Node Type . . . . . . . . . . . . : Hybrid
               IP Routing Enabled. . . . . . . . : No
               Some Address for Testing Purposes : 00-00-00-00-00-00
               WINS Proxy Enabled. . . . . . . . : No
               DNS Suffix Search List. . . . . . : network.lan
            Ethernet adapter Ethernet:
               Connection-specific DNS Suffix  . : network.lan
               Description . . . . . . . . . . . : Intel(R) PRO/1000 MT Desktop Adapter
               Physical Address. . . . . . . . . : 08-00-27-B8-42-C6
               DHCP Enabled. . . . . . . . . . . : Yes
               Autoconfiguration Enabled . . . . : Yes
               Link-local IPv6 Address . . . . . : fe80::606a:ae33:7ce1:b5e9%3(Preferred)
               IPv4 Address. . . . . . . . . . . : 10.0.2.15(Preferred)
               Subnet Mask . . . . . . . . . . . : 255.255.255.0
               Lease Obtained. . . . . . . . . . : Tuesday, January 30, 2018 11:25:31 PM
               Lease Expires . . . . . . . . . . : Wednesday, January 31, 2018 11:25:27 PM
               Default Gateway . . . . . . . . . : 10.0.2.2
               DHCP Server . . . . . . . . . . . : 10.0.2.2
               DHCPv6 IAID . . . . . . . . . . . : 34078759
               DHCPv6 Client DUID. . . . . . . . : 00-01-00-01-21-40-72-3F-08-00-27-B8-42-C6
               DNS Servers . . . . . . . . . . . : 10.0.2.3
               NetBIOS over Tcpip. . . . . . . . : Enabled
            Tunnel adapter isatap.network.lan:
               Media State . . . . . . . . . . . : Media disconnected
               Connection-specific DNS Suffix  . : network.lan
               Description . . . . . . . . . . . : Microsoft ISATAP Adapter
               Physical Address. . . . . . . . . : 00-00-00-00-00-00-00-E0
               DHCP Enabled. . . . . . . . . . . : No
               Autoconfiguration Enabled . . . . : Yes
            IPCONFIG;

        $os = $this->mockery(Os::class, [
            'getOsFamily' => 'Windows',
        ]);

        $os->expects('run')->with('ipconfig /all')->andReturn($ipconfig);

        $nic = new SystemNic(os: $os);

        $this->assertSame('080027B842C6', $nic->address());
    }

    /**
     * @runInSeparateProcess since the address is stored statically on the class
     * @preserveGlobalState disabled
     */
    public function testAddressOnDarwin(): void
    {
        $ifconfig = <<<'IFCONFIG'
            lo0: flags=8049<UP,LOOPBACK,RUNNING,MULTICAST> mtu 16384
                options=1203<RXCSUM,TXCSUM,TXSTATUS,SW_TIMESTAMP>
                inet 127.0.0.1 netmask 0xff000000
                inet6 ::1 prefixlen 128
                inet6 fe80::1%lo0 prefixlen 64 scopeid 0x1
                nd6 options=201<PERFORMNUD,DAD>
            en0: flags=8863<UP,BROADCAST,SMART,RUNNING,SIMPLEX,MULTICAST> mtu 1500
                options=10b<RXCSUM,TXCSUM,VLAN_HWTAGGING,AV>
                ether 00:00:00:00:00:00
                inet6 fe80::c70:76f5:aa1:5db1%en0 prefixlen 64 secured scopeid 0x7
                inet 10.53.8.112 netmask 0xfffffc00 broadcast 10.53.11.255
                nd6 options=201<PERFORMNUD,DAD>
                media: autoselect (1000baseT <full-duplex>)
                status: active
            en1: flags=8863<UP,BROADCAST,SMART,RUNNING,SIMPLEX,MULTICAST> mtu 1500
                ether ec:35:86:38:c8:c2
                inet6 fe80::aa:d44f:5f5f:7fd4%en1 prefixlen 64 secured scopeid 0x8
                inet 10.53.17.196 netmask 0xfffffc00 broadcast 10.53.19.255
                nd6 options=201<PERFORMNUD,DAD>
                media: autoselect
                status: active
            en2: flags=8963<UP,BROADCAST,SMART,RUNNING,PROMISC,SIMPLEX,MULTICAST> mtu 1500
                options=60<TSO4,TSO6>
                ether 32:00:18:9b:dc:60
                media: autoselect <full-duplex>
                status: inactive
            en3: flags=8963<UP,BROADCAST,SMART,RUNNING,PROMISC,SIMPLEX,MULTICAST> mtu 1500
                options=60<TSO4,TSO6>
                ether 32:00:18:9b:dc:61
                media: autoselect <full-duplex>
                status: inactive
            IFCONFIG;

        $os = $this->mockery(Os::class, [
            'getOsFamily' => 'Darwin',
        ]);

        $os->expects('run')->with('ifconfig')->andReturn($ifconfig);

        $nic = new SystemNic(os: $os);

        $this->assertSame('ec358638c8c2', $nic->address());
    }

    /**
     * @runInSeparateProcess since the address is stored statically on the class
     * @preserveGlobalState disabled
     */
    public function testAddressOnBsd(): void
    {
        $netstat = <<<'NETSTAT'
            Name    Mtu Network       Address              Ipkts Ierrs Idrop    Opkts Oerrs  Coll
            em0    1500 <Link#1>      00:00:00:00:00:00    65514     0     0    42918     0     0
            em1    1500 <Link#2>      08:00:27:d0:60:a0     1199     0     0      535     0     0
            lo0   16384 <Link#3>      lo0                      4     0     0        4     0     0
            NETSTAT;

        $os = $this->mockery(Os::class, [
            'getOsFamily' => 'BSD',
        ]);

        $os->expects('run')->with('netstat -i -f link')->andReturn($netstat);

        $nic = new SystemNic(os: $os);

        $this->assertSame('080027d060a0', $nic->address());
    }

    /**
     * @runInSeparateProcess since the address is stored statically on the class
     * @preserveGlobalState disabled
     */
    public function testAddressOnLinux(): void
    {
        $os = $this->mockery(Os::class, [
            'getOsFamily' => 'Linux',
        ]);

        $os->expects('glob')->with('/sys/class/net/*/address')->andReturn([
            '/sys/class/net/foo/address',
            '/sys/class/net/bar/address',
            '/sys/class/net/baz/address',
        ]);

        $os->expects('isReadable')->with('/sys/class/net/foo/address')->andReturnFalse();
        $os->expects('isReadable')->with('/sys/class/net/bar/address')->andReturnTrue();
        $os->expects('fileGetContents')->with('/sys/class/net/bar/address')->andReturn('00:00:00:00:00:00');
        $os->expects('isReadable')->with('/sys/class/net/baz/address')->andReturnTrue();
        $os->expects('fileGetContents')->with('/sys/class/net/baz/address')->andReturn('fe:dc:ba:98:76:54');

        $nic = new SystemNic(os: $os);

        $this->assertSame('fedcba987654', $nic->address());
    }

    /**
     * @runInSeparateProcess since the address is stored statically on the class
     * @preserveGlobalState disabled
     */
    public function testAddressOnLinuxFallsBackToNetstat(): void
    {
        $netstat = <<<'NETSTAT'
            Kernel Interface table
            docker0   Link encap:Ethernet  HWaddr 00:00:00:00:00:00
                      inet addr:172.17.0.1  Bcast:0.0.0.0  Mask:255.255.0.0
                      UP BROADCAST MULTICAST  MTU:1500  Metric:1
                      RX packets:0 errors:0 dropped:0 overruns:0 frame:0
                      TX packets:0 errors:0 dropped:0 overruns:0 carrier:0
                      collisions:0 txqueuelen:0
                      RX bytes:0 (0.0 B)  TX bytes:0 (0.0 B)
            enp3s0    Link encap:Ethernet  HWaddr fe:dc:ba:98:76:54
                      inet addr:10.0.0.1  Bcast:10.0.0.255  Mask:255.255.255.0
                      inet6 addr: ffee::ddcc:bbaa:9988:7766/64 Scope:Link
                      UP BROADCAST RUNNING MULTICAST  MTU:1500  Metric:1
                      RX packets:943077 errors:0 dropped:0 overruns:0 frame:0
                      TX packets:2168039 errors:0 dropped:0 overruns:0 carrier:0
                      collisions:0 txqueuelen:1000
                      RX bytes:748596414 (748.5 MB)  TX bytes:2930448282 (2.9 GB)
            lo        Link encap:Local Loopback
                      inet addr:127.0.0.1  Mask:255.0.0.0
                      inet6 addr: ::1/128 Scope:Host
                      UP LOOPBACK RUNNING  MTU:65536  Metric:1
                      RX packets:8302 errors:0 dropped:0 overruns:0 frame:0
                      TX packets:8302 errors:0 dropped:0 overruns:0 carrier:0
                      collisions:0 txqueuelen:1000
                      RX bytes:1094983 (1.0 MB)  TX bytes:1094983 (1.0 MB)
            NETSTAT;

        $os = $this->mockery(Os::class, [
            'getOsFamily' => 'Linux',
        ]);

        $os->expects('glob')->with('/sys/class/net/*/address')->andReturn([
            '/sys/class/net/foo/address',
            '/sys/class/net/bar/address',
        ]);

        $os->expects('isReadable')->with('/sys/class/net/foo/address')->andReturnFalse();
        $os->expects('isReadable')->with('/sys/class/net/bar/address')->andReturnTrue();
        $os->expects('fileGetContents')->with('/sys/class/net/bar/address')->andReturn(' foo bar ');
        $os->expects('run')->with('netstat -ie')->andReturn($netstat);

        $nic = new SystemNic(os: $os);

        $this->assertSame('fedcba987654', $nic->address());
    }

    /**
     * @runInSeparateProcess since the address is stored statically on the class
     * @preserveGlobalState disabled
     */
    public function testAddressNotFound(): void
    {
        $netstat = <<<'NETSTAT'
            Kernel Interface table
            docker0   Link encap:Ethernet  HWaddr 00:00:00:00:00:00
                      inet addr:172.17.0.1  Bcast:0.0.0.0  Mask:255.255.0.0
                      UP BROADCAST MULTICAST  MTU:1500  Metric:1
                      RX packets:0 errors:0 dropped:0 overruns:0 frame:0
                      TX packets:0 errors:0 dropped:0 overruns:0 carrier:0
                      collisions:0 txqueuelen:0
                      RX bytes:0 (0.0 B)  TX bytes:0 (0.0 B)
            NETSTAT;

        $os = $this->mockery(Os::class, [
            'getOsFamily' => 'Unknown',
        ]);

        $os->expects('run')->with('netstat -ie')->andReturn($netstat);

        $nic = new SystemNic(os: $os);

        $this->expectException(MacAddressNotFound::class);
        $this->expectExceptionMessage('Unable to fetch an address for this system');

        $nic->address();
    }
}
