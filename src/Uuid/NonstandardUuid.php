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

use BadMethodCallException;
use Identifier\Uuid\UuidInterface;
use Identifier\Uuid\Variant;
use InvalidArgumentException;

use function assert;
use function decbin;
use function is_string;
use function preg_match;
use function sprintf;
use function str_pad;
use function substr;
use function unpack;

use const STR_PAD_LEFT;

/**
 * @psalm-immutable
 */
final class NonstandardUuid implements UuidInterface
{
    use StandardUuid;

    public function __construct(string $uuid)
    {
        if ($uuid === '' || !preg_match($this->getValidationPattern(), $uuid)) {
            throw new InvalidArgumentException(sprintf(
                'Invalid nonstandard UUID: "%s"',
                $uuid,
            ));
        }

        $this->uuid = $uuid;
    }

    /**
     * @inheritDoc
     */
    public function __unserialize(array $data): void
    {
        assert(isset($data['uuid']), "'uuid' is not set in serialized data");
        assert(is_string($data['uuid']), "'uuid' in serialized data is not a string");
        assert($data['uuid'] !== '', "'uuid' in serialized data is an empty string");
        assert(
            preg_match($this->getValidationPattern(), $data['uuid']) === 1,
            sprintf(
                "'uuid' in serialized data is not a valid nonstandard UUID: \"%s\"",
                $data['uuid'],
            ),
        );

        $this->uuid = $data['uuid'];
    }

    /**
     * @link https://datatracker.ietf.org/doc/html/rfc4122#section-4.1.1 RFC 4122, § 4.1.1
     */
    public function getVariant(): Variant
    {
        /** @var int[] $parts */
        $parts = unpack('n*', $this->toBytes());

        // $parts[5] is a 16-bit, unsigned integer containing the variant bits
        // of the UUID. We convert this integer into a string containing a
        // binary representation, padded to 16 characters. We analyze the first
        // three characters (three most-significant bits) to determine the
        // variant.
        $binary = str_pad(decbin($parts[5]), 16, '0', STR_PAD_LEFT);
        $msb = substr($binary, 0, 3);

        return match (true) {
            $msb === '111' => Variant::ReservedFuture,
            $msb === '110' => Variant::ReservedMicrosoft,
            $msb === '100', $msb === '101' => Variant::Rfc4122,
            default => Variant::ReservedNcs,
        };
    }

    public function getVersion(): never
    {
        throw new BadMethodCallException('Nonstandard UUIDs do not have a version field');
    }

    protected function getValidationPattern(): string
    {
        // If the variant is anything other than RFC 4122, it is nonstandard.
        $nonstandardGeneral = '[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-7c-f][0-9a-f]{3}-[0-9a-f]{12}';

        // If the variant is RFC 4122 but the version is 0 or 9-15, it is nonstandard.
        $nonstandardRfc4122 = '[0-9a-f]{8}-[0-9a-f]{4}-[09a-f][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}';

        return '/^' . $nonstandardGeneral . '|' . $nonstandardRfc4122 . '$/Di';
    }
}
