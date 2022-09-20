<?php

/**
 * This file is part of ramsey/identifier
 *
 * ramsey/identifier is open source software: you can distribute
 * it and/or modify it under the terms of the MIT License
 * (the "License"). You may not use this file except in
 * compliance with the License.
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or
 * implied. See the License for the specific language governing
 * permissions and limitations under the License.
 *
 * @copyright Copyright (c) Ben Ramsey <ben@benramsey.com>
 * @license https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Ramsey\Identifier\Uuid;

use Identifier\Uuid\Version;

use function count;
use function explode;
use function hexdec;
use function strlen;
use function strspn;
use function substr;
use function unpack;

/**
 * @internal
 *
 * @psalm-immutable
 */
trait Validation
{
    abstract protected function getVersion(): Version;

    private function getVariantFromUuid(string $uuid): ?int
    {
        return match (strlen($uuid)) {
            36 => hexdec(substr($uuid, 19, 1)) & 0xc,
            32 => hexdec(substr($uuid, 16, 1)) & 0xc,
            16 => (static function (string $uuid): int {
                /** @var positive-int[] $parts */
                $parts = unpack('n*', $uuid, 8);

                return ($parts[1] & 0xc000) >> 12;
            })($uuid),
            default => null,
        };
    }

    private function getVersionFromUuid(string $uuid): ?int
    {
        return match (strlen($uuid)) {
            36 => (int) hexdec(substr($uuid, 14, 1)),
            32 => (int) hexdec(substr($uuid, 12, 1)),
            16 => (static function (string $uuid): int {
                /** @var positive-int[] $parts */
                $parts = unpack('n*', $uuid, 6);

                return ($parts[1] & 0xf000) >> 12;
            })($uuid),
            default => null,
        };
    }

    private function hasValidFormat(string $uuid): bool
    {
        $mask = '0123456789abcdefABCDEF';

        return match (strlen($uuid)) {
            36 => $this->isValidStringLayout($uuid, $mask),
            32 => strspn($uuid, $mask) === 32,
            16 => true,
            default => false,
        };
    }

    private function isMax(string $uuid): bool
    {
        // We support uppercase, lowercase, and mixed case.
        $mask = 'fF';

        return match (strlen($uuid)) {
            36 => $this->isValidStringLayout($uuid, $mask),
            32 => strspn($uuid, $mask) === 32,
            16 => $uuid === "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff",
            default => false,
        };
    }

    private function isNil(string $uuid): bool
    {
        return match (strlen($uuid)) {
            36 => $uuid === '00000000-0000-0000-0000-000000000000',
            32 => $uuid === '00000000000000000000000000000000',
            16 => $uuid === "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
            default => false,
        };
    }

    private function isValid(string $uuid): bool
    {
        return $this->hasValidFormat($uuid)
            && $this->getVariantFromUuid($uuid) === 8
            && $this->getVersionFromUuid($uuid) === $this->getVersion()->value;
    }

    private function isValidStringLayout(string $uuid, string $mask): bool
    {
        $format = explode('-', $uuid);

        return count($format) === 5
            && strspn($format[0], $mask) === 8
            && strspn($format[1], $mask) === 4
            && strspn($format[2], $mask) === 4
            && strspn($format[3], $mask) === 4
            && strspn($format[4], $mask) === 12;
    }
}
