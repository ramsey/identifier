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

/**
 * An operating system.
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
     * Returns the operating system family of the current system (i.e., PHP_OS_FAMILY).
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
