<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Service\Random;

use Ramsey\Identifier\Service\Random\StaticBytesService;
use Ramsey\Test\Identifier\TestCase;

class StaticBytesServiceTest extends TestCase
{
    public function testGetRandomBytesWithLengthExactlyAsValueProvided(): void
    {
        $bytes = "\xab\xcd\xef\x01\x23\x45\x67\x89";
        $service = new StaticBytesService($bytes);

        $this->assertSame($bytes, $service->getRandomBytes(8));
    }

    public function testGetRandomBytesWithLengthGreaterThanValueProvided(): void
    {
        $bytes = "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff";
        $service = new StaticBytesService($bytes);

        $this->assertSame($bytes, $service->getRandomBytes(20));
    }

    public function testGetRandomBytesWithLengthLessThanValueProvided(): void
    {
        $bytes = "\xff\xff\xff\xff\xab\xcd\xef\x01\x23\x45\x67\x89\xff\xff\xff\xff";
        $service = new StaticBytesService($bytes);

        $this->assertSame("\xff\xff\xff\xff\xab\xcd\xef\x01", $service->getRandomBytes(8));
    }
}
