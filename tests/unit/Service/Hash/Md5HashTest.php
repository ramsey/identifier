<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Service\Hash;

use PHPUnit\Framework\TestCase;
use Ramsey\Identifier\Service\Hash\Md5Hash;

class Md5HashTest extends TestCase
{
    public function testHash(): void
    {
        $hash = new Md5Hash();

        $this->assertSame('3858f62230ac3c915f300c664312c63f', $hash->hash('foobar'));
    }

    public function testHashBinary(): void
    {
        $hash = new Md5Hash();

        $this->assertSame(
            "\x38\x58\xf6\x22\x30\xac\x3c\x91\x5f\x30\x0c\x66\x43\x12\xc6\x3f",
            $hash->hash('foobar', true),
        );
    }
}
