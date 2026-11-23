<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Service\Os;

use PHPUnit\Framework\Attributes\TestWith;
use Ramsey\Identifier\Service\Os\MissingFunction;
use Ramsey\Identifier\Service\Os\PhpOs;
use Ramsey\Test\Identifier\TestCase;
use ReflectionClass;

use function escapeshellarg;
use function file_get_contents;
use function getmypid;
use function microtime;
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

        $properties = [
            'hasEscapeShellArg',
            'hasFileGetContents',
            'hasGetMyPid',
            'hasGlob',
            'hasIsReadable',
            'hasMicrotime',
            'hasShellExec',
        ];

        foreach ($properties as $property) {
            $class->setStaticPropertyValue($property, null);
        }

        parent::tearDown();
    }

    public function testEscapeShellArg(): void
    {
        $this->assertSame(escapeshellarg("it's a group"), $this->os->escapeShellArg("it's a group"));
    }

    public function testFileGetContents(): void
    {
        $expectedContents = file_get_contents(__FILE__);

        $this->assertSame($expectedContents, $this->os->fileGetContents(__FILE__));
    }

    public function testGetMyPid(): void
    {
        $this->assertSame(getmypid(), $this->os->getMyPid());
    }

    public function testGetMyPidReturnsFalseWhenItsFunctionIsNotAvailable(): void
    {
        (new ReflectionClass(PhpOs::class))->setStaticPropertyValue('hasGetMyPid', false);

        $this->assertFalse($this->os->getMyPid());
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

    public function testMicrotime(): void
    {
        $before = microtime(true);
        $microtime = $this->os->microtime();
        $after = microtime(true);

        $this->assertGreaterThanOrEqual($before, $microtime);
        $this->assertLessThanOrEqual($after, $microtime);
    }

    public function testMicrotimeReturnsTheTimeWhenItsFunctionIsNotAvailable(): void
    {
        (new ReflectionClass(PhpOs::class))->setStaticPropertyValue('hasMicrotime', false);

        $before = microtime(true);
        $microtime = $this->os->microtime();
        $after = microtime(true);

        // The two ways of reading the time may round the same microsecond to
        // floats one step apart.
        $this->assertGreaterThanOrEqual($before - 0.000001, $microtime);
        $this->assertLessThanOrEqual($after + 0.000001, $microtime);
    }

    public function testRun(): void
    {
        $expected = match (PHP_OS_FAMILY) {
            'Windows' => '"foo bar"',
            default => 'foo bar',
        };

        $this->assertSame($expected, trim($this->os->run('echo "foo bar"')));
    }

    public function testRunPassesAPipeToTheShell(): void
    {
        $filter = match (PHP_OS_FAMILY) {
            'Windows' => 'findstr two',
            default => 'grep two',
        };

        $this->assertSame('two', trim($this->os->run('echo two | ' . $filter)));
    }

    public function testRunDoesNotEscapeAQuotedArgument(): void
    {
        $expected = match (PHP_OS_FAMILY) {
            'Windows' => '"R&D Team"',
            default => 'R&D Team',
        };

        $this->assertSame($expected, trim($this->os->run('echo "R&D Team"')));
    }

    public function testRunReturnsTheErrorOutput(): void
    {
        $output = $this->os->run('ramsey-identifier-no-such-command');

        $this->assertStringContainsString('ramsey-identifier-no-such-command', $output);
    }

    #[TestWith(['hasEscapeShellArg', 'escapeShellArg', 'escapeshellarg() is not available on this system'])]
    #[TestWith(['hasFileGetContents', 'fileGetContents', 'file_get_contents() is not available on this system'])]
    #[TestWith(['hasGlob', 'glob', 'glob() is not available on this system'])]
    #[TestWith(['hasIsReadable', 'isReadable', 'is_readable() is not available on this system'])]
    #[TestWith(['hasShellExec', 'run', 'shell_exec() is not available on this system'])]
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
