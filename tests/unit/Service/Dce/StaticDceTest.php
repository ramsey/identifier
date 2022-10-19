<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Service\Dce;

use Ramsey\Identifier\Exception\DceIdentifierNotFound;
use Ramsey\Identifier\Service\Dce\StaticDce;
use Ramsey\Test\Identifier\TestCase;

class StaticDceTest extends TestCase
{
    public function testUserId(): void
    {
        $dce = new StaticDce(userId: 1001);

        $this->assertSame(1001, $dce->userId());
    }

    public function testGroupId(): void
    {
        $dce = new StaticDce(groupId: 2001);

        $this->assertSame(2001, $dce->groupId());
    }

    public function testGetOrgId(): void
    {
        $dce = new StaticDce(orgId: 3001);

        $this->assertSame(3001, $dce->orgId());
    }

    public function testUserIdThrowsExceptionForMissingId(): void
    {
        $dce = new StaticDce();

        $this->expectException(DceIdentifierNotFound::class);
        $this->expectExceptionMessage(
            'To use the user identifier, you must set $userId when instantiating '
            . StaticDce::class,
        );

        $dce->userId();
    }

    public function testGroupIdThrowsExceptionForMissingId(): void
    {
        $dce = new StaticDce();

        $this->expectException(DceIdentifierNotFound::class);
        $this->expectExceptionMessage(
            'To use the group identifier, you must set $groupId when instantiating '
            . StaticDce::class,
        );

        $dce->groupId();
    }

    public function testGetOrgIdThrowsExceptionForMissingId(): void
    {
        $dce = new StaticDce();

        $this->expectException(DceIdentifierNotFound::class);
        $this->expectExceptionMessage(
            'To use the org identifier, you must set $orgId when instantiating '
            . StaticDce::class,
        );

        $dce->orgId();
    }
}
