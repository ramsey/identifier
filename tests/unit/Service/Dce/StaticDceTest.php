<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Service\Dce;

use Ramsey\Identifier\Exception\DceIdentifierNotFound;
use Ramsey\Identifier\Exception\InvalidArgument;
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

    public function testWhenUserIdIsNegative(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('The DCE user ID must be a positive 32-bit integer or null');

        /** @phpstan-ignore argument.type */
        new StaticDce(userId: -1);
    }

    public function testWhenUserIdIsOutOfBounds(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('The DCE user ID must be a positive 32-bit integer or null');

        /** @phpstan-ignore argument.type */
        new StaticDce(userId: 0x100000000);
    }

    public function testWhenGroupIdIsNegative(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('The DCE group ID must be a positive 32-bit integer or null');

        /** @phpstan-ignore argument.type */
        new StaticDce(groupId: -1);
    }

    public function testWhenGroupIdIsOutOfBounds(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('The DCE group ID must be a positive 32-bit integer or null');

        /** @phpstan-ignore argument.type */
        new StaticDce(groupId: 0x100000000);
    }

    public function testWhenOrgIdIsNegative(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('The DCE org ID must be a positive 32-bit integer or null');

        /** @phpstan-ignore argument.type */
        new StaticDce(orgId: -1);
    }

    public function testWhenOrgIdIsOutOfBounds(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('The DCE org ID must be a positive 32-bit integer or null');

        /** @phpstan-ignore argument.type */
        new StaticDce(orgId: 0x100000000);
    }
}
