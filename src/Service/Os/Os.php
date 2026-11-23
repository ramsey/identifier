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

/**
 * An operating system interface
 */
interface Os
{
    /**
     * Returns the argument quoted and escaped for use in a shell command
     *
     * @link https://www.php.net/manual/en/function.escapeshellarg.php PHP escapeshellarg() function
     *
     * @param string $argument The argument to escape
     *
     * @throws MissingFunction if a PHP function the method needs is not
     *     available on this system
     */
    public function escapeShellArg(string $argument): string;

    /**
     * Returns the entire contents of the named file
     *
     * @param string $filename The name of the file to read
     *
     * @throws MissingFunction if a PHP function the method needs is not
     *     available on this system
     */
    public function fileGetContents(string $filename): string;

    /**
     * Returns the identifier of the current process, or false if it cannot be
     * read
     *
     * @link https://www.php.net/manual/en/function.getmypid.php PHP getmypid() function
     */
    public function getMyPid(): int | false;

    /**
     * Returns the operating system family of the current system (i.e.,
     * `PHP_OS_FAMILY`)
     *
     * @return "Windows" | "BSD" | "Darwin" | "Solaris" | "Linux" | "Unknown"
     */
    public function getOsFamily(): string;

    /**
     * Returns an array of paths matching the pattern
     *
     * @link https://www.php.net/manual/en/function.glob.php PHP glob() function
     *
     * @param string $pattern Pattern to match
     *
     * @return string[]
     *
     * @throws MissingFunction if a PHP function the method needs is not
     *     available on this system
     */
    public function glob(string $pattern): array;

    /**
     * Returns true if the file or directory exists and is readable
     *
     * @param string $filename The path to the file or directory
     *
     * @throws MissingFunction if a PHP function the method needs is not
     *     available on this system
     */
    public function isReadable(string $filename): bool;

    /**
     * Returns the current Unix timestamp in seconds, with microseconds as the
     * fraction
     *
     * @link https://www.php.net/manual/en/function.microtime.php PHP microtime() function
     */
    public function microtime(): float;

    /**
     * Executes the given command and returns its full output
     *
     * @param string $command The command to execute
     *
     * @throws MissingFunction if a PHP function the method needs is not
     *     available on this system
     */
    public function run(string $command): string;
}
