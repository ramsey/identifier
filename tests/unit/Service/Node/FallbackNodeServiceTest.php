<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Service\Node;

use Ramsey\Identifier\Exception\NodeNotFoundException;
use Ramsey\Identifier\Service\Node\FallbackNodeService;
use Ramsey\Identifier\Service\Node\NodeServiceInterface;
use Ramsey\Identifier\Service\Node\StaticNodeService;
use Ramsey\Test\Identifier\TestCase;

class FallbackNodeServiceTest extends TestCase
{
    public function testGetNodeStepsThroughNodeServicesToReturnNode(): void
    {
        $service1 = $this->mockery(NodeServiceInterface::class);
        $service1->allows()->getNode()->andThrows(new NodeNotFoundException('could not find node'));

        $service2 = $this->mockery(NodeServiceInterface::class);
        $service2->allows()->getNode()->andThrows(new NodeNotFoundException('could not find node'));

        $service3 = new StaticNodeService('aabbcc001122');

        $service = new FallbackNodeService([$service1, $service2, $service3]);

        $this->assertSame('abbbcc001122', $service->getNode());

        // Calling getNode() again steps through all the services again.
        $this->assertSame('abbbcc001122', $service->getNode());
    }

    public function testGetNodeWithoutAnyNodeServices(): void
    {
        $service = new FallbackNodeService([]);

        $this->expectException(NodeNotFoundException::class);
        $this->expectExceptionMessage('Unable to find a suitable node service');

        $service->getNode();
    }

    public function testGetNodeCannotObtainASuitableNode(): void
    {
        $service1 = $this->mockery(NodeServiceInterface::class);
        $service1->expects()->getNode()->andThrows(new NodeNotFoundException('could not find node'));

        $service2 = $this->mockery(NodeServiceInterface::class);
        $service2->expects()->getNode()->andThrows(new NodeNotFoundException('could not find node'));

        $service = new FallbackNodeService([$service1, $service2]);

        $this->expectException(NodeNotFoundException::class);
        $this->expectExceptionMessage('Unable to find a suitable node service');

        $service->getNode();
    }
}