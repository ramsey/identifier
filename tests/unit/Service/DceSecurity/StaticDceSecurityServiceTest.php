<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Service\DceSecurity;

use Ramsey\Identifier\Exception\DceSecurityIdentifierNotFound;
use Ramsey\Identifier\Service\DceSecurity\StaticDceSecurityService;
use Ramsey\Test\Identifier\TestCase;

class StaticDceSecurityServiceTest extends TestCase
{
    public function testGetPersonIdentifier(): void
    {
        $service = new StaticDceSecurityService(personId: 1001);

        $this->assertSame(1001, $service->getPersonIdentifier());
    }

    public function testGetGroupIdentifier(): void
    {
        $service = new StaticDceSecurityService(groupId: 2001);

        $this->assertSame(2001, $service->getGroupIdentifier());
    }

    public function testGetOrgIdentifier(): void
    {
        $service = new StaticDceSecurityService(orgId: 3001);

        $this->assertSame(3001, $service->getOrgIdentifier());
    }

    public function testGetPersonIdentifierThrowsExceptionForMissingId(): void
    {
        $service = new StaticDceSecurityService();

        $this->expectException(DceSecurityIdentifierNotFound::class);
        $this->expectExceptionMessage(
            'To use the person identifier, you must set $personId when instantiating '
            . StaticDceSecurityService::class,
        );

        $service->getPersonIdentifier();
    }

    public function testGetGroupIdentifierThrowsExceptionForMissingId(): void
    {
        $service = new StaticDceSecurityService();

        $this->expectException(DceSecurityIdentifierNotFound::class);
        $this->expectExceptionMessage(
            'To use the group identifier, you must set $groupId when instantiating '
            . StaticDceSecurityService::class,
        );

        $service->getGroupIdentifier();
    }

    public function testGetOrgIdentifierThrowsExceptionForMissingId(): void
    {
        $service = new StaticDceSecurityService();

        $this->expectException(DceSecurityIdentifierNotFound::class);
        $this->expectExceptionMessage(
            'To use the org identifier, you must set $orgId when instantiating '
            . StaticDceSecurityService::class,
        );

        $service->getOrgIdentifier();
    }
}
