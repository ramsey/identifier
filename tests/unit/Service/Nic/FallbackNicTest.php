<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Service\Nic;

use Ramsey\Identifier\Exception\MacAddressNotFound;
use Ramsey\Identifier\Service\Nic\FallbackNic;
use Ramsey\Identifier\Service\Nic\Nic;
use Ramsey\Identifier\Service\Nic\StaticNic;
use Ramsey\Test\Identifier\TestCase;

class FallbackNicTest extends TestCase
{
    public function testAddressStepsThroughNicsToReturnAddress(): void
    {
        $nic1 = $this->mockery(Nic::class);
        $nic1->expects('address')->twice()->andThrows(new MacAddressNotFound('could not find address'));

        $nic2 = $this->mockery(Nic::class);
        $nic2->expects('address')->twice()->andThrows(new MacAddressNotFound('could not find address'));

        $nic3 = new StaticNic('aabbcc001122');

        $fallbackNic = new FallbackNic([$nic1, $nic2, $nic3]);

        $this->assertSame('abbbcc001122', $fallbackNic->address());

        // Calling address() again steps through all the NICs again.
        $this->assertSame('abbbcc001122', $fallbackNic->address());
    }

    public function testAddressWithoutAnyNics(): void
    {
        $nic = new FallbackNic([]);

        $this->expectException(MacAddressNotFound::class);
        $this->expectExceptionMessage('Unable to find a MAC address');

        $nic->address();
    }

    public function testAddressCannotObtainASuitableAddress(): void
    {
        $nic1 = $this->mockery(Nic::class);
        $nic1->expects('address')->andThrows(new MacAddressNotFound('could not find address'));

        $nic2 = $this->mockery(Nic::class);
        $nic2->expects('address')->andThrows(new MacAddressNotFound('could not find address'));

        $fallbackNic = new FallbackNic([$nic1, $nic2]);

        $this->expectException(MacAddressNotFound::class);
        $this->expectExceptionMessage('Unable to find a MAC address');

        $fallbackNic->address();
    }
}
