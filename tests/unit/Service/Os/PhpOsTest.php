<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Service\Os;

use PHPUnit\Framework\Attributes\TestWith;
use Ramsey\Identifier\Service\Os\MissingFunction;
use Ramsey\Identifier\Service\Os\PhpOs;
use Ramsey\Test\Identifier\TestCase;
use ReflectionClass;

use function file_get_contents;
use function str_repeat;
use function trim;

use const DIRECTORY_SEPARATOR;
use const PHP_OS_FAMILY;

class PhpOsTest extends TestCase
{
    private PhpOs $os;

    protected function setUp(): void
    {
        $this->os = new PhpOs();
    }

    protected function tearDown(): void
    {
        $class = new ReflectionClass(PhpOs::class);

        foreach (['hasEscapeShellCmd', 'hasFileGetContents', 'hasGlob', 'hasIsReadable', 'hasShellExec'] as $property) {
            $class->setStaticPropertyValue($property, null);
        }

        parent::tearDown();
    }

    public function testFileGetContents(): void
    {
        $expectedContents = file_get_contents(__FILE__);

        $this->assertSame($expectedContents, $this->os->fileGetContents(__FILE__));
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

    #[TestWith(['hasFileGetContents', 'fileGetContents', 'file_get_contents() is not available on this system'])]
    #[TestWith(['hasGlob', 'glob', 'glob() is not available on this system'])]
    #[TestWith(['hasIsReadable', 'isReadable', 'is_readable() is not available on this system'])]
    #[TestWith(['hasShellExec', 'run', 'shell_exec() is not available on this system'])]
    #[TestWith(['hasEscapeShellCmd', 'run', 'escapeshellcmd() is not available on this system'])]
    public function testMethodThrowsWhenItsFunctionIsNotAvailable(
        string $property,
        string $method,
        string $message,
    ): void {
        (new ReflectionClass(PhpOs::class))->setStaticPropertyValue($property, false);

        $this->expectException(MissingFunction::class);
        $this->expectExceptionMessage($message);

        $this->os->{$method}(__FILE__);
    }

    public function testMethodsOfOtherFunctionsWorkWhenOneFunctionIsNotAvailable(): void
    {
        (new ReflectionClass(PhpOs::class))->setStaticPropertyValue('hasShellExec', false);

        $this->assertTrue($this->os->isReadable(__FILE__));
        $this->assertSame([__FILE__], $this->os->glob(__FILE__));
        $this->assertSame(file_get_contents(__FILE__), $this->os->fileGetContents(__FILE__));
    }
}
