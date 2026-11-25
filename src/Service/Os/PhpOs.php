<?php

/**
 * This file is part of ramsey/identifier
 *
 * Copyright © Ben Ramsey <ben@ramsey.dev> and Contributors
 *
 * ramsey/identifier is free software: you can redistribute it and/or modify it
 * under the terms of the GNU Lesser General Public License as published by the
 * Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.
 *
 * ramsey/identifier is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License
 * for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with ramsey/identifier. If not, see <https://www.gnu.org/licenses/>.
 *
 * SPDX-License-Identifier: LGPL-3.0-or-later
 */

declare(strict_types=1);

namespace Ramsey\Identifier\Service\Os;

use DateTimeImmutable;
use Override;

use function file_get_contents;
use function function_exists;
use function getmypid;
use function glob;
use function is_readable;
use function microtime;
use function shell_exec;

use const GLOB_NOSORT;
use const PHP_OS_FAMILY;

/**
 * An OS interface that uses pure PHP functions to interact with the operating
 * system
 */
final class PhpOs implements Os
{
    /**
     * Whether file_get_contents() is available; null until it is first needed
     */
    private static ?bool $hasFileGetContents = null;

    /**
     * Whether getmypid() is available; null until it is first needed
     */
    private static ?bool $hasGetMyPid = null;

    /**
     * Whether glob() is available; null until it is first needed
     */
    private static ?bool $hasGlob = null;

    /**
     * Whether is_readable() is available; null until it is first needed
     */
    private static ?bool $hasIsReadable = null;

    /**
     * Whether microtime() is available; null until it is first needed
     */
    private static ?bool $hasMicrotime = null;

    /**
     * Whether shell_exec() is available; null until it is first needed
     */
    private static ?bool $hasShellExec = null;

    /**
     * @throws MissingFunction if file_get_contents() is not available on this
     *     system
     */
    #[Override]
    public function fileGetContents(string $filename): string
    {
        self::$hasFileGetContents ??= function_exists('file_get_contents');

        if (!self::$hasFileGetContents) {
            throw new MissingFunction('file_get_contents() is not available on this system');
        }

        return (string) file_get_contents($filename);
    }

    #[Override]
    public function getMyPid(): int | false
    {
        self::$hasGetMyPid ??= function_exists('getmypid');

        if (!self::$hasGetMyPid) {
            return false;
        }

        return getmypid();
    }

    #[Override]
    public function getOsFamily(): string
    {
        /** @var "Windows" | "BSD" | "Darwin" | "Solaris" | "Linux" | "Unknown" */
        return PHP_OS_FAMILY;
    }

    /**
     * @return string[]
     *
     * @throws MissingFunction if glob() is not available on this system
     */
    #[Override]
    public function glob(string $pattern): array
    {
        self::$hasGlob ??= function_exists('glob');

        if (!self::$hasGlob) {
            throw new MissingFunction('glob() is not available on this system');
        }

        $paths = @glob($pattern, GLOB_NOSORT);

        return $paths ?: [];
    }

    /**
     * @throws MissingFunction if is_readable() is not available on this system
     */
    #[Override]
    public function isReadable(string $filename): bool
    {
        self::$hasIsReadable ??= function_exists('is_readable');

        if (!self::$hasIsReadable) {
            throw new MissingFunction('is_readable() is not available on this system');
        }

        return is_readable($filename);
    }

    /**
     * Returns the time from a new date-time object when microtime() is not
     * available on this system
     */
    #[Override]
    public function microtime(): float
    {
        self::$hasMicrotime ??= function_exists('microtime');

        if (!self::$hasMicrotime) {
            return (float) (new DateTimeImmutable())->format('U.u');
        }

        return microtime(true);
    }

    /**
     * @throws MissingFunction if shell_exec() is not available on this system
     */
    #[Override]
    public function run(string $command): string
    {
        self::$hasShellExec ??= function_exists('shell_exec');

        if (!self::$hasShellExec) {
            throw new MissingFunction('shell_exec() is not available on this system');
        }

        // Redirect stderr to stdout.
        return (string) @shell_exec($command . ' 2>&1');
    }
}
