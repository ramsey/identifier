<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Service\Dce;

use Exception;
use Hamcrest\Type\IsInteger;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\TestWith;
use Psr\SimpleCache\CacheException;
use Psr\SimpleCache\CacheInterface;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Service\Dce\DceIdentifierNotFound;
use Ramsey\Identifier\Service\Dce\SystemDce;
use Ramsey\Identifier\Service\Os\MissingFunction;
use Ramsey\Identifier\Service\Os\Os;
use Ramsey\Test\Identifier\TestCase;

use function implode;

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

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testGroupId(): void
    {
        $dce = new SystemDce();

        $this->assertGreaterThanOrEqual(0, $dce->groupId());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testGroupIdFromCache(): void
    {
        $cache = $this->mockery(CacheInterface::class);
        $cache->expects('get')->with('__ramsey_id_63ba1027')->andReturn(5001);

        $dce = new SystemDce(cache: $cache);

        $this->assertSame(5001, $dce->groupId());

        // Assert subsequent calls do not attempt to fetch from cache.
        $this->assertSame(5001, $dce->groupId());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testGroupIdFromCacheSetsIdentifierOnCache(): void
    {
        $cache = $this->mockery(CacheInterface::class);
        $cache->expects('get')->with('__ramsey_id_63ba1027')->andReturnNull();
        $cache->expects('set')->with('__ramsey_id_63ba1027', new IsInteger())->andReturnTrue();

        $dce = new SystemDce(cache: $cache);
        $groupId = $dce->groupId();

        $this->assertGreaterThanOrEqual(0, $groupId);

        // Assert subsequent calls do not attempt to fetch from cache.
        $this->assertSame($groupId, $dce->groupId());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testGroupIdFromCacheThrowsException(): void
    {
        $exception = new class extends Exception implements CacheException {
        };

        $cache = $this->mockery(CacheInterface::class);
        $cache->expects('get')->with('__ramsey_id_63ba1027')->andThrow($exception);

        $dce = new SystemDce(cache: $cache);

        $this->expectException(DceIdentifierNotFound::class);
        $this->expectExceptionMessage(
            'Unable to get a group identifier using the system DCE service; please provide a custom identifier '
            . 'or use a different DCE service class',
        );

        $dce->groupId();
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testGroupIdThrowsExceptionWhenIdentifierNotFound(): void
    {
        $cache = $this->mockery(CacheInterface::class);
        $cache->expects('get')->with('__ramsey_id_63ba1027')->andReturn(-1);

        $dce = new SystemDce(cache: $cache);

        $this->expectException(DceIdentifierNotFound::class);
        $this->expectExceptionMessage(
            'Unable to get a group identifier using the system DCE '
            . 'service; please provide a custom identifier or use '
            . 'a different DCE service class',
        );

        $dce->groupId();
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testGroupIdPosix(): void
    {
        $os = $this->mockery(Os::class);
        $os->expects('getOsFamily')->andReturn('Unknown');
        $os->expects('run')->with('id -g')->andReturn('42');

        $dce = new SystemDce(os: $os);

        $this->assertSame(42, $dce->groupId());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testGroupIdNotFoundPosix(): void
    {
        $os = $this->mockery(Os::class);
        $os->expects('getOsFamily')->andReturn('Unknown');
        $os->expects('run')->with('id -g')->andReturn('');

        $dce = new SystemDce(os: $os);

        $this->expectException(DceIdentifierNotFound::class);
        $this->expectExceptionMessage(
            'Unable to get a group identifier using the system DCE '
            . 'service; please provide a custom identifier or use '
            . 'a different DCE service class',
        );

        $dce->groupId();
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    #[DataProvider('provideWindowsGroupValues')]
    public function testGroupIdWindows(string $whoamiResponse, ?int $expectedId): void
    {
        $os = $this->mockery(Os::class);
        $os->expects('getOsFamily')->andReturn('Windows');
        $os->expects('run')->with('whoami /groups /fo csv /nh')->andReturn($whoamiResponse);

        $dce = new SystemDce(os: $os);

        if ($expectedId === null) {
            $this->expectException(DceIdentifierNotFound::class);
            $this->expectExceptionMessage(
                'Unable to get a group identifier using the system DCE '
                . 'service; please provide a custom identifier or use '
                . 'a different DCE service class',
            );

            $dce->groupId();
        } else {
            $this->assertSame($expectedId, $dce->groupId());
        }
    }

    /**
     * @return array<string, array{whoamiResponse: string, expectedId: int | null}>
     */
    public static function provideWindowsGroupValues(): array
    {
        $attributes = '"Mandatory group, Enabled by default, Enabled group"';
        $domain = 'S-1-5-21-3102628041-531311518-2056980507';

        $everyone = '"Everyone","Well-known group","S-1-1-0",' . $attributes;
        $administrators = '"BUILTIN\Administrators","Alias","S-1-5-32-544",' . $attributes;
        $users = '"BUILTIN\Users","Alias","S-1-5-32-545",' . $attributes;
        $sshUsers = '"BUILTIN\OpenSSH Users","Alias","S-1-5-32-585",' . $attributes;
        $network = '"NT AUTHORITY\NETWORK","Well-known group","S-1-5-2",' . $attributes;
        $authenticated = '"NT AUTHORITY\Authenticated Users","Well-known group","S-1-5-11",' . $attributes;
        $ntlm = '"NT AUTHORITY\NTLM Authentication","Well-known group","S-1-5-64-10",' . $attributes;
        $domainAdmins = '"EXAMPLE\Domain Admins","Group","' . $domain . '-512",' . $attributes;
        $domainUsers = '"EXAMPLE\Domain Users","Group","' . $domain . '-513",' . $attributes;
        $schemaAdmins = '"EXAMPLE\Schema Admins","Group","' . $domain . '-518",' . $attributes;
        $label = '"Mandatory Label\Medium Mandatory Level","Label","S-1-16-8192",""';

        $response = static fn (string ...$rows): string => implode("\r\n", $rows) . "\r\n";

        return [
            'a local administrator' => [
                'whoamiResponse' => $response($everyone, $administrators, $users, $network, $authenticated, $label),
                'expectedId' => 544,
            ],
            'a local user who is not an administrator' => [
                'whoamiResponse' => $response($everyone, $users, $sshUsers, $network, $authenticated, $ntlm, $label),
                'expectedId' => 545,
            ],
            'the same groups in another order' => [
                'whoamiResponse' => $response($everyone, $sshUsers, $users, $network, $authenticated, $ntlm, $label),
                'expectedId' => 545,
            ],
            'an administrator of a domain' => [
                'whoamiResponse' => $response(
                    $everyone,
                    $administrators,
                    $users,
                    $network,
                    $domainAdmins,
                    $schemaAdmins,
                    $label,
                ),
                'expectedId' => 544,
            ],
            'a lower domain RID before a built-in group' => [
                'whoamiResponse' => $response($everyone, $domainAdmins, $users, $label),
                'expectedId' => 545,
            ],
            'groups of a domain only' => [
                'whoamiResponse' => $response($everyone, $schemaAdmins, $domainUsers, $network, $label),
                'expectedId' => 513,
            ],
            'lines that end with a line feed only' => [
                'whoamiResponse' => $everyone . "\n" . $users . "\n",
                'expectedId' => 545,
            ],
            'one row with no line ending' => [
                'whoamiResponse' => $users,
                'expectedId' => 545,
            ],
            'group names in another language' => [
                'whoamiResponse' => $response(
                    '"Jeder","Bekannte Gruppe","S-1-1-0",' . $attributes,
                    '"VORDEFINIERT\Benutzer","Alias","S-1-5-32-545",' . $attributes,
                ),
                'expectedId' => 545,
            ],
            'a group name with a comma and a quotation mark' => [
                'whoamiResponse' => $response(
                    '"EXAMPLE\Sales, ""East""","Group","' . $domain . '-1105",' . $attributes,
                ),
                'expectedId' => 1105,
            ],
            'a byte that is a line break in some encodings, in a name with a comma' => [
                'whoamiResponse' => $response(
                    '"EXAMPLE\Ventes ' . "\x85" . ' Paris, Est","Group","' . $domain . '-1105",' . $attributes,
                ),
                'expectedId' => 1105,
            ],
            'the highest RID that fits in 32 bits' => [
                'whoamiResponse' => $response('"BUILTIN\Example","Alias","S-1-5-32-4294967295",' . $attributes),
                'expectedId' => 4294967295,
            ],
            'an RID of zero' => [
                'whoamiResponse' => $response('"BUILTIN\Example","Alias","S-1-5-32-0",' . $attributes),
                'expectedId' => 0,
            ],
            'an RID too large for 32 bits beside one that fits' => [
                'whoamiResponse' => $response(
                    '"BUILTIN\Example","Alias","S-1-5-32-4294967296",' . $attributes,
                    $sshUsers,
                ),
                'expectedId' => 585,
            ],

            // These should all fail with an exception:
            'empty output' => ['whoamiResponse' => '', 'expectedId' => null],
            'blank lines' => ['whoamiResponse' => "\r\n\r\n", 'expectedId' => null],
            'the text of an error' => [
                'whoamiResponse' => "'whoami' is not recognized as an internal or external command,\r\n"
                    . "operable program or batch file.\r\n",
                'expectedId' => null,
            ],
            'no built-in, machine, or domain group' => [
                'whoamiResponse' => $response($everyone, $network, $authenticated, $ntlm, $label),
                'expectedId' => null,
            ],
            'a row with too few fields' => [
                'whoamiResponse' => $response('"BUILTIN\Users","Alias"'),
                'expectedId' => null,
            ],
            'an SID in the first field' => [
                'whoamiResponse' => $response('"S-1-5-32-545","Alias","BUILTIN\Users",' . $attributes),
                'expectedId' => null,
            ],
            'an RID too large for 32 bits' => [
                'whoamiResponse' => $response('"BUILTIN\Example","Alias","S-1-5-32-4294967296",' . $attributes),
                'expectedId' => null,
            ],
            'an RID of eleven digits' => [
                'whoamiResponse' => $response('"BUILTIN\Example","Alias","S-1-5-32-10000000000",' . $attributes),
                'expectedId' => null,
            ],
            'a built-in RID with a leading zero' => [
                'whoamiResponse' => $response('"BUILTIN\Users","Alias","S-1-5-32-0545",' . $attributes),
                'expectedId' => null,
            ],
            'a machine RID with a leading zero' => [
                'whoamiResponse' => $response('"EXAMPLE\Group","Group","S-1-5-21-1-2-3-0513",' . $attributes),
                'expectedId' => null,
            ],
            'a machine SID with a leading zero in an earlier part' => [
                'whoamiResponse' => $response('"EXAMPLE\Group","Group","S-1-5-21-1-02-3-513",' . $attributes),
                'expectedId' => null,
            ],
            'a machine SID with parts of zero' => [
                'whoamiResponse' => $response('"EXAMPLE\Group","Group","S-1-5-21-0-0-0-513",' . $attributes),
                'expectedId' => 513,
            ],
            'an RID that is not a number' => [
                'whoamiResponse' => $response('"BUILTIN\Example","Alias","S-1-5-32-54x",' . $attributes),
                'expectedId' => null,
            ],
            'a machine SID with too few parts' => [
                'whoamiResponse' => $response('"EXAMPLE\Group","Group","S-1-5-21-1-2-513",' . $attributes),
                'expectedId' => null,
            ],
            'a machine SID with too many parts' => [
                'whoamiResponse' => $response('"EXAMPLE\Group","Group","S-1-5-21-1-2-3-4-513",' . $attributes),
                'expectedId' => null,
            ],
            'a built-in SID with an extra part' => [
                'whoamiResponse' => $response('"BUILTIN\Example","Alias","S-1-5-32-545-1",' . $attributes),
                'expectedId' => null,
            ],
            'a service SID' => [
                'whoamiResponse' => $response(
                    '"NT SERVICE\Example","Well-known group","S-1-5-80-19088743-1985229328-4294967295-1324",'
                    . $attributes,
                ),
                'expectedId' => null,
            ],
            'an SID with text after it' => [
                'whoamiResponse' => $response('"BUILTIN\Users","Alias","S-1-5-32-545 ",' . $attributes),
                'expectedId' => null,
            ],
            'an SID with text before it' => [
                'whoamiResponse' => $response('"BUILTIN\Users","Alias","XS-1-5-32-545",' . $attributes),
                'expectedId' => null,
            ],
        ];
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testUserId(): void
    {
        $dce = new SystemDce();

        $this->assertGreaterThanOrEqual(0, $dce->userId());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testUserIdFromCache(): void
    {
        $cache = $this->mockery(CacheInterface::class);
        $cache->expects('get')->with('__ramsey_id_63e41035')->andReturn(6001);

        $dce = new SystemDce(cache: $cache);

        $this->assertSame(6001, $dce->userId());

        // Assert subsequent calls do not attempt to fetch from cache.
        $this->assertSame(6001, $dce->userId());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testUserIdFromCacheSetsIdentifierOnCache(): void
    {
        $cache = $this->mockery(CacheInterface::class);
        $cache->expects('get')->with('__ramsey_id_63e41035')->andReturnNull();
        $cache->expects('set')->with('__ramsey_id_63e41035', new IsInteger())->andReturnTrue();

        $dce = new SystemDce(cache: $cache);
        $userId = $dce->userId();

        $this->assertGreaterThanOrEqual(0, $userId);

        // Assert subsequent calls do not attempt to fetch from cache.
        $this->assertSame($userId, $dce->userId());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testUserIdFromCacheThrowsException(): void
    {
        $exception = new class extends Exception implements CacheException {
        };

        $cache = $this->mockery(CacheInterface::class);
        $cache->expects('get')->with('__ramsey_id_63e41035')->andThrow($exception);

        $dce = new SystemDce(cache: $cache);

        $this->expectException(DceIdentifierNotFound::class);
        $this->expectExceptionMessage(
            'Unable to get a user identifier using the system DCE service; please provide a custom identifier '
            . 'or use a different DCE service class',
        );

        $dce->userId();
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testUserIdThrowsExceptionWhenIdentifierNotFound(): void
    {
        $cache = $this->mockery(CacheInterface::class);
        $cache->expects('get')->with('__ramsey_id_63e41035')->andReturn(-1);

        $dce = new SystemDce(cache: $cache);

        $this->expectException(DceIdentifierNotFound::class);
        $this->expectExceptionMessage(
            'Unable to get a user identifier using the system DCE '
            . 'service; please provide a custom identifier or use '
            . 'a different DCE service class',
        );

        $dce->userId();
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testUserIdPosix(): void
    {
        $os = $this->mockery(Os::class);
        $os->expects('getOsFamily')->andReturn('Unknown');
        $os->expects('run')->with('id -u')->andReturn('142');

        $dce = new SystemDce(os: $os);

        $this->assertSame(142, $dce->userId());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testUserIdNotFoundPosix(): void
    {
        $os = $this->mockery(Os::class);
        $os->expects('getOsFamily')->andReturn('Unknown');
        $os->expects('run')->with('id -u')->andReturn('');

        $dce = new SystemDce(os: $os);

        $this->expectException(DceIdentifierNotFound::class);
        $this->expectExceptionMessage(
            'Unable to get a user identifier using the system DCE '
            . 'service; please provide a custom identifier or use '
            . 'a different DCE service class',
        );

        $dce->userId();
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    #[DataProvider('provideWindowsUserValues')]
    public function testUserIdWindows(
        string $whoamiResponse,
        ?int $expectedId = null,
    ): void {
        $os = $this->mockery(Os::class);
        $os->expects('getOsFamily')->andReturn('Windows');

        $os->expects('run')
            ->with('whoami /user /fo csv /nh')
            ->andReturn($whoamiResponse);

        $dce = new SystemDce(os: $os);

        if ($expectedId === null) {
            $this->expectException(DceIdentifierNotFound::class);
            $this->expectExceptionMessage(
                'Unable to get a user identifier using the system DCE '
                . 'service; please provide a custom identifier or use '
                . 'a different DCE service class',
            );

            $dce->userId();
        } else {
            $this->assertSame($expectedId, $dce->userId());
        }
    }

    /**
     * @return list<array{whoamiResponse: string, expectedId?: int}>
     */
    public static function provideWindowsUserValues(): array
    {
        return [
            [
                'whoamiResponse' => '"Melilot Sackville","S-1-5-21-7375663-6890924511-1272660413-2944159"',
                'expectedId' => 2944159,
            ],
            [
                'whoamiResponse' => '"Brutus Sandheaver","S-1-3-12-1234525106-3567804255-30012867-1437"',
                'expectedId' => 1437,
            ],
            [
                'whoamiResponse' => '"Cora Rumble","S-345"',
                'expectedId' => 345,
            ],

            // These should all fail with an exception:
            ['whoamiResponse' => ''],
            ['whoamiResponse' => '"Cora Rumble","345"'],
        ];
    }

    public function testWhenOrgIdIsNegative(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('The DCE org ID must be a positive 32-bit integer or null');

        /** @phpstan-ignore argument.type */
        new SystemDce(orgId: -1);
    }

    public function testWhenOrgIdIsOutOfBounds(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('The DCE org ID must be a positive 32-bit integer or null');

        /** @phpstan-ignore argument.type */
        new SystemDce(orgId: 0x100000000);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testGroupIdThrowsExceptionWhenAFunctionIsNotAvailable(): void
    {
        $missingFunction = new MissingFunction('shell_exec() is not available on this system');

        $os = $this->mockery(Os::class);
        $os->expects('getOsFamily')->andReturn('Unknown');
        $os->expects('run')->with('id -g')->andThrow($missingFunction);

        $dce = new SystemDce(os: $os);

        try {
            $dce->groupId();
            $this->fail('Expected ' . DceIdentifierNotFound::class);
        } catch (DceIdentifierNotFound $exception) {
            $this->assertSame(
                'Unable to get a group identifier using the system DCE service; please provide a custom identifier '
                . 'or use a different DCE service class',
                $exception->getMessage(),
            );
            $this->assertSame($missingFunction, $exception->getPrevious());
        }
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testUserIdThrowsExceptionWhenAFunctionIsNotAvailable(): void
    {
        $missingFunction = new MissingFunction('shell_exec() is not available on this system');

        $os = $this->mockery(Os::class);
        $os->expects('getOsFamily')->andReturn('Unknown');
        $os->expects('run')->with('id -u')->andThrow($missingFunction);

        $dce = new SystemDce(os: $os);

        try {
            $dce->userId();
            $this->fail('Expected ' . DceIdentifierNotFound::class);
        } catch (DceIdentifierNotFound $exception) {
            $this->assertSame(
                'Unable to get a user identifier using the system DCE service; please provide a custom identifier '
                . 'or use a different DCE service class',
                $exception->getMessage(),
            );
            $this->assertSame($missingFunction, $exception->getPrevious());
        }
    }

    /**
     * @param int<0, 4294967295> $orgId
     */
    #[TestWith([0])]
    #[TestWith([0xffffffff])]
    public function testOrgIdAcceptsIdentifiersAtTheLimits(int $orgId): void
    {
        $this->assertSame($orgId, (new SystemDce(orgId: $orgId))->orgId());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testGroupIdAcceptsZeroFromCache(): void
    {
        $cache = $this->mockery(CacheInterface::class);
        $cache->allows('get')->andReturn(0);

        $this->assertSame(0, (new SystemDce(cache: $cache))->groupId());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testGroupIdNotFoundWhenPosixCommandReturnsOnlyWhitespace(): void
    {
        $os = $this->mockery(Os::class);
        $os->expects('getOsFamily')->andReturn('Unknown');
        $os->expects('run')->with('id -g')->andReturn(" \n");

        $this->expectException(DceIdentifierNotFound::class);

        (new SystemDce(os: $os))->groupId();
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testUserIdAcceptsZeroFromCache(): void
    {
        $cache = $this->mockery(CacheInterface::class);
        $cache->allows('get')->andReturn(0);

        $this->assertSame(0, (new SystemDce(cache: $cache))->userId());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testUserIdNotFoundWhenPosixCommandReturnsOnlyWhitespace(): void
    {
        $os = $this->mockery(Os::class);
        $os->expects('getOsFamily')->andReturn('Unknown');
        $os->expects('run')->with('id -u')->andReturn(" \n");

        $this->expectException(DceIdentifierNotFound::class);

        (new SystemDce(os: $os))->userId();
    }
}
