<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Service\Os;

use Ramsey\Identifier\Service\Os\PhpOs;
use Ramsey\Test\Identifier\TestCase;

use function file_get_contents;
use function str_repeat;
use function trim;

use const DIRECTORY_SEPARATOR;
use const PHP_INT_SIZE;
use const PHP_OS_FAMILY;

class PhpOsTest extends TestCase
{
    private PhpOs $os;

    protected function setUp(): void
    {
        $this->os = new PhpOs();
    }

    public function testFileGetContents(): void
    {
        $expectedContents = file_get_contents(__FILE__);

        $this->assertSame($expectedContents, $this->os->fileGetContents(__FILE__));
    }

    public function testGetIntSize(): void
    {
        $this->assertSame(PHP_INT_SIZE, $this->os->getIntSize());
    }

    public function testGetOsFamily(): void
    {
        $this->assertSame(PHP_OS_FAMILY, $this->os->getOsFamily());
    }

    public function testGlob(): void
    {
        $this->assertSame(
            [
                __FILE__,
            ],
            $this->os->glob(__DIR__ . DIRECTORY_SEPARATOR . '*.php'),
        );
    }

    public function testGlobReturnsEmptyArrayOnFailure(): void
    {
        $this->assertSame([], $this->os->glob(str_repeat('x', 3000)));
    }

    public function testIsReadable(): void
    {
        $this->assertTrue($this->os->isReadable(__FILE__));
    }

    public function testIsReadableReturnsFalse(): void
    {
        $this->assertFalse($this->os->isReadable(__DIR__ . '/foo-bar'));
    }

    public function testRun(): void
    {
        $expected = match (PHP_OS_FAMILY) {
            'Windows' => '"foo bar"',
            default => 'foo bar',
        };

        $this->assertSame($expected, trim($this->os->run('echo "foo bar"')));
    }
}
