<?php

/**
 * This file is part of ramsey/identifier
 *
 * ramsey/identifier is open source software: you can distribute
 * it and/or modify it under the terms of the MIT License
 * (the "License"). You may not use this file except in
 * compliance with the License.
 *
 * @copyright Copyright (c) Ben Ramsey <ben@benramsey.com>
 * @license https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Ramsey\Identifier\Service\Os;

use Ramsey\Identifier\Exception\MissingFunction;

use function file_get_contents;
use function function_exists;
use function glob;
use function is_readable;
use function shell_exec;

use const GLOB_NOSORT;
use const PHP_OS_FAMILY;

/**
 * An OS that uses pure PHP functions to interact with the operating system
 */
class PhpOs implements Os
{
    /**
     * @throws MissingFunction if a required PHP function is not available on this system
     */
    public function __construct()
    {
        if (!function_exists('shell_exec')) {
            throw new MissingFunction('shell_exec() is not available on this system'); // @codeCoverageIgnore
        }

        if (!function_exists('file_get_contents')) {
            throw new MissingFunction('file_get_contents() is not available on this system'); // @codeCoverageIgnore
        }

        if (!function_exists('glob')) {
            throw new MissingFunction('glob() is not available on this system'); // @codeCoverageIgnore
        }

        if (!function_exists('is_readable')) {
            throw new MissingFunction('is_readable() is not available on this system'); // @codeCoverageIgnore
        }
    }

    public function fileGetContents(string $filename): string
    {
        return (string) file_get_contents($filename);
    }

    public function getOsFamily(): string
    {
        /** @var "Windows" | "BSD" | "Darwin" | "Solaris" | "Linux" | "Unknown" */
        return PHP_OS_FAMILY;
    }

    /**
     * @inheritDoc
     */
    public function glob(string $pattern): array
    {
        $paths = @glob($pattern, GLOB_NOSORT);

        return $paths ?: [];
    }

    public function isReadable(string $filename): bool
    {
        return is_readable($filename);
    }

    public function run(string $command): string
    {
        // Redirect stderr to stdout.
        $command .= ' 2>&1';

        /** @psalm-suppress ForbiddenCode */
        return (string) @shell_exec($command);
    }
}
