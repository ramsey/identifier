<?php

/**
 * This file is part of ramsey/identifier
 *
 * ramsey/identifier is free software: you can redistribute it and/or modify it under the terms of the GNU Lesser
 * General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your
 * option) any later version.
 *
 * ramsey/identifier is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the
 * implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License
 * for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License along with ramsey/identifier. If not, see
 * <https://www.gnu.org/licenses/>.
 *
 * @copyright Copyright (c) Ben Ramsey <ben@ramsey.dev> and Contributors
 * @license https://opensource.org/license/lgpl-3-0/ GNU Lesser General Public License version 3 or later
 */

declare(strict_types=1);

namespace Ramsey\Identifier\Service\Os;

/**
 * An operating system interface.
 */
interface Os
{
    /**
     * Returns the entire contents of the named file.
     *
     * @param string $filename The name of the file to read.
     */
    public function fileGetContents(string $filename): string;

    /**
     * Returns the operating system family of the current system (i.e., `PHP_OS_FAMILY`).
     *
     * @return "Windows" | "BSD" | "Darwin" | "Solaris" | "Linux" | "Unknown"
     */
    public function getOsFamily(): string;

    /**
     * Returns an array of paths matching the pattern.
     *
     * @link https://www.php.net/manual/en/function.glob.php PHP glob() function.
     *
     * @param string $pattern Pattern to match.
     *
     * @return string[]
     */
    public function glob(string $pattern): array;

    /**
     * Returns true if the file or directory exists and is readable.
     *
     * @param string $filename The path to the file or directory.
     */
    public function isReadable(string $filename): bool;

    /**
     * Executes the given command and returns its full output.
     *
     * @param string $command The command to execute.
     */
    public function run(string $command): string;
}
