<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Service\Dce;

use Hamcrest\Type\IsInteger;
use Mockery;
use Psr\SimpleCache\CacheInterface;
use Ramsey\Identifier\Exception\DceIdentifierNotFound;
use Ramsey\Identifier\Service\Dce\SystemDce;
use Ramsey\Identifier\Service\Os\Os;
use Ramsey\Test\Identifier\TestCase;

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
        $cache->expects('get')->with('__ramsey_identifier_27a5')->andReturn(5001);

        $dce = new SystemDce(cache: $cache);

        $this->assertSame(5001, $dce->groupId());

        // Assert subsequent calls do not attempt to fetch from cache.
        $this->assertSame(5001, $dce->groupId());
    }

    /**
     * @runInSeparateProcess since the identifier is stored statically on the class
     * @preserveGlobalState disabled
     */
    public function testGroupIdFromCacheSetsIdentifierOnCache(): void
    {
        $cache = $this->mockery(CacheInterface::class);
        $cache->expects('get')->with('__ramsey_identifier_27a5')->andReturnNull();
        $cache->expects('set')->with('__ramsey_identifier_27a5', new IsInteger())->andReturnTrue();

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
        $cache->expects('get')->with('__ramsey_identifier_27a5')->andReturn(-1);

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
    public function testGroupIdPosix(): void
    {
        $os = $this->mockery(Os::class);
        $os->expects('getOsFamily')->andReturn('Unknown');
        $os->expects('run')->with('id -g')->andReturn('42');

        $dce = new SystemDce(os: $os);

        $this->assertSame(42, $dce->groupId());
    }

    /**
     * @runInSeparateProcess since the identifier is stored statically on the class
     * @preserveGlobalState disabled
     */
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

    /**
     * @runInSeparateProcess since the identifier is stored statically on the class
     * @preserveGlobalState disabled
     * @dataProvider provideWindowsGroupValues
     */
    public function testGroupIdWindows(
        string $netUserResponse,
        ?string $wmicGroupResponse = null,
        ?string $expectedGroup = null,
        ?int $expectedId = null,
    ): void {
        $os = $this->mockery(Os::class);
        $os->expects('getOsFamily')->andReturn('Windows');

        $os->expects('run')
            ->with('net user %username% | findstr /b /i "Local Group Memberships"')
            ->andReturn($netUserResponse);

        if ($wmicGroupResponse === null) {
            $os->expects('run')
                ->with(Mockery::pattern('/^wmic group get name,sid \| findstr \/b \/i .*$/'))
                ->never();
        } else {
            $os->expects('run')
                ->with(Mockery::pattern("/^wmic group get name,sid \| findstr \/b \/i (\"|\')$expectedGroup(\"|\')$/"))
                ->andReturn($wmicGroupResponse);
        }

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
     * @return array<array{netUserResponse: string, wmicGroupResponse?: string, expectedGroup?: string, expectedId?: int}>
     */
    public function provideWindowsGroupValues(): array
    {
        return [
            [
                'netUserResponse' => 'Local Group Memberships    *Administrators  *Users',
                'wmicGroupResponse' => 'Administrators  S-1-5-32-544',
                'expectedGroup' => 'Administrators',
                'expectedId' => 544,
            ],
            [
                'netUserResponse' => 'Local Group Memberships    Users',
                'wmicGroupResponse' => 'Users  S-1-5-32-545',
                'expectedGroup' => 'Users',
                'expectedId' => 545,
            ],
            [
                'netUserResponse' => 'Local Group Memberships    Guests  Nobody',
                'wmicGroupResponse' => 'Guests  S-1-5-32-546',
                'expectedGroup' => 'Guests',
                'expectedId' => 546,
            ],
            [
                'netUserResponse' => 'Local Group Memberships   Some Group  Another Group',
                'wmicGroupResponse' => 'Some Group    S-1-5-80-19088743-1985229328-4294967295-1324',
                'expectedGroup' => 'Some Group',
                'expectedId' => 1324,
            ],

            // These should all fail with an exception:
            ['netUserResponse' => ''],
            ['netUserResponse' => 'foobar'],
            ['netUserResponse' => 'foo,bar,baz'],
            ['netUserResponse' => '1234'],
            ['netUserResponse' => 'Local Group Memberships'],
            ['netUserResponse' => 'Local Group Memberships    ****  Foo'],
            [
                'netUserResponse' => 'Local Group Memberships    Users',
                'wmicGroupResponse' => '',
                'expectedGroup' => 'Users',
            ],
            [
                'netUserResponse' => 'Local Group Memberships    Users',
                'wmicGroupResponse' => 'Users  Not a valid SID string',
                'expectedGroup' => 'Users',
            ],
            [
                'netUserResponse' => 'Local Group Memberships    Users',
                'wmicGroupResponse' => 'Users  344aab9758bb0d018b93739e7893fb3a',
                'expectedGroup' => 'Users',
            ],
        ];
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
        $cache->expects('get')->with('__ramsey_identifier_690f')->andReturn(6001);

        $dce = new SystemDce(cache: $cache);

        $this->assertSame(6001, $dce->userId());

        // Assert subsequent calls do not attempt to fetch from cache.
        $this->assertSame(6001, $dce->userId());
    }

    /**
     * @runInSeparateProcess since the identifier is stored statically on the class
     * @preserveGlobalState disabled
     */
    public function testUserIdFromCacheSetsIdentifierOnCache(): void
    {
        $cache = $this->mockery(CacheInterface::class);
        $cache->expects('get')->with('__ramsey_identifier_690f')->andReturnNull();
        $cache->expects('set')->with('__ramsey_identifier_690f', new IsInteger())->andReturnTrue();

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
        $cache->expects('get')->with('__ramsey_identifier_690f')->andReturn(-1);

        $dce = new SystemDce(cache: $cache);

        $this->expectException(DceIdentifierNotFound::class);
        $this->expectExceptionMessage(
            'Unable to get a user identifier using the system DCE '
            . 'service; please provide a custom identifier or use '
            . 'a different DCE service class',
        );

        $dce->userId();
    }

    /**
     * @runInSeparateProcess since the identifier is stored statically on the class
     * @preserveGlobalState disabled
     */
    public function testUserIdPosix(): void
    {
        $os = $this->mockery(Os::class);
        $os->expects('getOsFamily')->andReturn('Unknown');
        $os->expects('run')->with('id -u')->andReturn('142');

        $dce = new SystemDce(os: $os);

        $this->assertSame(142, $dce->userId());
    }

    /**
     * @runInSeparateProcess since the identifier is stored statically on the class
     * @preserveGlobalState disabled
     */
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

    /**
     * @runInSeparateProcess since the identifier is stored statically on the class
     * @preserveGlobalState disabled
     * @dataProvider provideWindowsUserValues
     */
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
     * @return array<array{whoamiResponse: string, expectedId?: int}>
     */
    public function provideWindowsUserValues(): array
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
}
