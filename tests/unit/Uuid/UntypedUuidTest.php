<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Uuid;

use DateTimeImmutable;
use DateTimeInterface;
use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use Ramsey\Identifier\Exception\BadMethodCall;
use Ramsey\Identifier\Exception\CannotDetermineVersion;
use Ramsey\Identifier\Exception\InvalidArgument;
use Ramsey\Identifier\Uuid;
use Ramsey\Identifier\Uuid\MaxUuid;
use Ramsey\Identifier\Uuid\NilUuid;
use Ramsey\Identifier\Uuid\NonstandardUuid;
use Ramsey\Identifier\Uuid\UntypedUuid;
use Ramsey\Identifier\Uuid\UuidV1;
use Ramsey\Identifier\Uuid\UuidV2;
use Ramsey\Identifier\Uuid\UuidV3;
use Ramsey\Identifier\Uuid\UuidV4;
use Ramsey\Identifier\Uuid\UuidV5;
use Ramsey\Identifier\Uuid\UuidV6;
use Ramsey\Identifier\Uuid\UuidV7;
use Ramsey\Identifier\Uuid\UuidV8;
use Ramsey\Identifier\Uuid\Variant;
use Ramsey\Identifier\Uuid\Version;
use Ramsey\Test\Identifier\TestCase;
use Throwable;

use function class_exists;
use function json_encode;
use function serialize;
use function unserialize;

class UntypedUuidTest extends TestCase
{
    /**
     * @param non-empty-string $value
     * @param array{
     *     type: class-string<Uuid>,
     *     variant: (Variant | class-string<Exception>),
     *     version: (Version | class-string<Exception>),
     *     json: string,
     *     string: string,
     *     bytes: string,
     *     hex: string,
     *     int: (int | string),
     *     urn: string,
     *     node: (string | class-string<Exception>),
     *     date: (DateTimeInterface | class-string<Exception>),
     * } $expected
     */
    #[DataProvider('provideValidUuids')]
    public function testUntypedUuids(string $value, array $expected): void
    {
        $untypedUuid = new UntypedUuid($value);
        $typedUuid = $untypedUuid->toTypedUuid();
        $unserializedUntypedUuid = unserialize(serialize($untypedUuid));

        $this->assertInstanceOf($expected['type'], $typedUuid);
        $this->assertSame($typedUuid, $untypedUuid->toTypedUuid());
        $this->assertSame(0, $untypedUuid->compareTo($typedUuid));
        $this->assertTrue($untypedUuid->equals($typedUuid));
        $this->assertSame(0, $untypedUuid->compareTo($unserializedUntypedUuid));
        $this->assertTrue($untypedUuid->equals($unserializedUntypedUuid));

        $this->assertSame($expected['variant'], $untypedUuid->getVariant());
        $this->assertSame($expected['json'], json_encode($untypedUuid));
        $this->assertSame($expected['string'], $untypedUuid->toString());
        $this->assertSame($expected['string'], (string) $untypedUuid);
        $this->assertSame($expected['bytes'], $untypedUuid->toBytes());
        $this->assertSame($expected['hex'], $untypedUuid->toHexadecimal());
        $this->assertSame($expected['int'], $untypedUuid->toInteger());
        $this->assertSame($expected['urn'], $untypedUuid->toUrn());

        $version = null;
        $node = null;
        $date = null;

        if ($expected['version'] instanceof Version) {
            $this->assertSame($expected['version'], $untypedUuid->getVersion());
        } else {
            try {
                $version = $untypedUuid->getVersion();
            } catch (Throwable $exception) {
                $this->assertInstanceOf($expected['version'], $exception);
            } finally {
                $this->assertNull($version, 'Expected exception of type ' . $expected['version']);
            }
        }

        if (!class_exists($expected['node'])) {
            $this->assertSame($expected['node'], $untypedUuid->getNode());
        } else {
            try {
                $node = $untypedUuid->getNode();
                $this->fail('Expected exception of type ' . $expected['node']);
            } catch (Throwable $exception) {
                $this->assertInstanceOf($expected['node'], $exception);
            } finally {
                $this->assertNull($node, 'Expected exception of type ' . $expected['node']);
            }
        }

        if ($expected['date'] instanceof DateTimeInterface) {
            $this->assertSame(
                $expected['date']->format('Y-m-d H:i:s.v'),
                $untypedUuid->getDateTime()->format('Y-m-d H:i:s.v'),
            );
        } else {
            try {
                $date = $untypedUuid->getDateTime();
                $this->fail('Expected exception of type ' . $expected['date']);
            } catch (Throwable $exception) {
                $this->assertInstanceOf($expected['date'], $exception);
            } finally {
                $this->assertNull($date, 'Expected exception of type ' . $expected['date']);
            }
        }
    }

    /**
     * @return array<string, array{
     *     value: string,
     *     expected: array{
     *         type: class-string<Uuid>,
     *         variant: (Variant | class-string<Exception>),
     *         version: (Version | class-string<Exception>),
     *         json: string,
     *         string: string,
     *         bytes: string,
     *         hex: string,
     *         int: (int | string),
     *         urn: string,
     *         node: (string | class-string<Exception>),
     *         date: (DateTimeInterface | class-string<Exception>),
     *     },
     * }>
     */
    public static function provideValidUuids(): array
    {
        $expectedMax = [
            'type' => MaxUuid::class,
            'variant' => Variant::Rfc4122,
            'version' => CannotDetermineVersion::class,
            'json' => '"ffffffff-ffff-ffff-ffff-ffffffffffff"',
            'string' => 'ffffffff-ffff-ffff-ffff-ffffffffffff',
            'bytes' => "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff",
            'hex' => 'ffffffffffffffffffffffffffffffff',
            'int' => '340282366920938463463374607431768211455',
            'urn' => 'urn:uuid:ffffffff-ffff-ffff-ffff-ffffffffffff',
            'node' => BadMethodCall::class,
            'date' => BadMethodCall::class,
        ];

        $expectedNil = [
            'type' => NilUuid::class,
            'variant' => Variant::Rfc4122,
            'version' => CannotDetermineVersion::class,
            'json' => '"00000000-0000-0000-0000-000000000000"',
            'string' => '00000000-0000-0000-0000-000000000000',
            'bytes' => "\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00",
            'hex' => '00000000000000000000000000000000',
            'int' => 0,
            'urn' => 'urn:uuid:00000000-0000-0000-0000-000000000000',
            'node' => BadMethodCall::class,
            'date' => BadMethodCall::class,
        ];

        $expectedV1 = [
            'type' => UuidV1::class,
            'variant' => Variant::Rfc4122,
            'version' => Version::GregorianTime,
            'json' => '"637c935a-4107-11ed-b878-0242ac120002"',
            'string' => '637c935a-4107-11ed-b878-0242ac120002',
            'bytes' => "\x63\x7c\x93\x5a\x41\x07\x11\xed\xb8\x78\x02\x42\xac\x12\x00\x02",
            'hex' => '637c935a410711edb8780242ac120002',
            'int' => '132240405058036425907283047087500034050',
            'urn' => 'urn:uuid:637c935a-4107-11ed-b878-0242ac120002',
            'node' => '0242ac120002',
            'date' => new DateTimeImmutable('2022-09-30 21:32:30.803849'),
        ];

        $expectedV2 = [
            'type' => UuidV2::class,
            'variant' => Variant::Rfc4122,
            'version' => Version::DceSecurity,
            'json' => '"0001e240-4103-21ed-8001-0100499602d2"',
            'string' => '0001e240-4103-21ed-8001-0100499602d2',
            'bytes' => "\x00\x01\xe2\x40\x41\x03\x21\xed\x80\x01\x01\x00\x49\x96\x02\xd2",
            'hex' => '0001e240410321ed80010100499602d2',
            'int' => '9781212151673653104156696601035474',
            'urn' => 'urn:uuid:0001e240-4103-21ed-8001-0100499602d2',
            'node' => '0100499602d2',
            'date' => new DateTimeImmutable('2022-09-30 21:01:05.906074'),
        ];

        $expectedV3 = [
            'type' => UuidV3::class,
            'variant' => Variant::Rfc4122,
            'version' => Version::NameMd5,
            'json' => '"0d7f3039-f255-332a-b432-61905e2d036d"',
            'string' => '0d7f3039-f255-332a-b432-61905e2d036d',
            'bytes' => "\x0d\x7f\x30\x39\xf2\x55\x33\x2a\xb4\x32\x61\x90\x5e\x2d\x03\x6d",
            'hex' => '0d7f3039f255332ab43261905e2d036d',
            'int' => '17940363792902440749691632624822190957',
            'urn' => 'urn:uuid:0d7f3039-f255-332a-b432-61905e2d036d',
            'node' => BadMethodCall::class,
            'date' => BadMethodCall::class,
        ];

        $expectedV4 = [
            'type' => UuidV4::class,
            'variant' => Variant::Rfc4122,
            'version' => Version::Random,
            'json' => '"0b94395a-0002-49cf-8fcf-af141d60af90"',
            'string' => '0b94395a-0002-49cf-8fcf-af141d60af90',
            'bytes' => "\x0b\x94\x39\x5a\x00\x02\x49\xcf\x8f\xcf\xaf\x14\x1d\x60\xaf\x90",
            'hex' => '0b94395a000249cf8fcfaf141d60af90',
            'int' => '15391131116582029933200135300944277392',
            'urn' => 'urn:uuid:0b94395a-0002-49cf-8fcf-af141d60af90',
            'node' => BadMethodCall::class,
            'date' => BadMethodCall::class,
        ];

        $expectedV5 = [
            'type' => UuidV5::class,
            'variant' => Variant::Rfc4122,
            'version' => Version::NameSha1,
            'json' => '"0d7f3039-f255-532a-b432-61905e2d036d"',
            'string' => '0d7f3039-f255-532a-b432-61905e2d036d',
            'bytes' => "\x0d\x7f\x30\x39\xf2\x55\x53\x2a\xb4\x32\x61\x90\x5e\x2d\x03\x6d",
            'hex' => '0d7f3039f255532ab43261905e2d036d',
            'int' => '17940363792902591865419084453469029229',
            'urn' => 'urn:uuid:0d7f3039-f255-532a-b432-61905e2d036d',
            'node' => BadMethodCall::class,
            'date' => BadMethodCall::class,
        ];

        $expectedV6 = [
            'type' => UuidV6::class,
            'variant' => Variant::Rfc4122,
            'version' => Version::ReorderedGregorianTime,
            'json' => '"1ed41076-37c9-635a-b878-0242ac120002"',
            'string' => '1ed41076-37c9-635a-b878-0242ac120002',
            'bytes' => "\x1e\xd4\x10\x76\x37\xc9\x63\x5a\xb8\x78\x02\x42\xac\x12\x00\x02",
            'hex' => '1ed4107637c9635ab8780242ac120002',
            'int' => '40977940692298833571978962982688391170',
            'urn' => 'urn:uuid:1ed41076-37c9-635a-b878-0242ac120002',
            'node' => '0242ac120002',
            'date' => new DateTimeImmutable('2022-09-30 21:32:30.803849'),
        ];

        $expectedV7 = [
            'type' => UuidV7::class,
            'variant' => Variant::Rfc4122,
            'version' => Version::UnixTime,
            'json' => '"01839050-b198-71ed-b878-0242ac120002"',
            'string' => '01839050-b198-71ed-b878-0242ac120002',
            'bytes' => "\x01\x83\x90\x50\xb1\x98\x71\xed\xb8\x78\x02\x42\xac\x12\x00\x02",
            'hex' => '01839050b19871edb8780242ac120002',
            'int' => '2012345944452046749472145290585833474',
            'urn' => 'urn:uuid:01839050-b198-71ed-b878-0242ac120002',
            'node' => BadMethodCall::class,
            'date' => new DateTimeImmutable('2022-09-30 21:32:31.000000'),
        ];

        $expectedV8 = [
            'type' => UuidV8::class,
            'variant' => Variant::Rfc4122,
            'version' => Version::Custom,
            'json' => '"0b94395a-0002-89cf-8fcf-af141d60af90"',
            'string' => '0b94395a-0002-89cf-8fcf-af141d60af90',
            'bytes' => "\x0b\x94\x39\x5a\x00\x02\x89\xcf\x8f\xcf\xaf\x14\x1d\x60\xaf\x90",
            'hex' => '0b94395a000289cf8fcfaf141d60af90',
            'int' => '15391131116582332164655038958237953936',
            'urn' => 'urn:uuid:0b94395a-0002-89cf-8fcf-af141d60af90',
            'node' => BadMethodCall::class,
            'date' => BadMethodCall::class,
        ];

        $expectedNonstandard = [
            'type' => NonstandardUuid::class,
            'variant' => Variant::Ncs,
            'version' => CannotDetermineVersion::class,
            'json' => '"88b46f48-2fc8-3ce3-1056-b1b6a94e4207"',
            'string' => '88b46f48-2fc8-3ce3-1056-b1b6a94e4207',
            'bytes' => "\x88\xb4\x6f\x48\x2f\xc8\x3c\xe3\x10\x56\xb1\xb6\xa9\x4e\x42\x07",
            'hex' => '88b46f482fc83ce31056b1b6a94e4207',
            'int' => '181711877927966402206605959143917240839',
            'urn' => 'urn:uuid:88b46f48-2fc8-3ce3-1056-b1b6a94e4207',
            'node' => BadMethodCall::class,
            'date' => BadMethodCall::class,
        ];

        // This looks like a version 2 UUID, but the domain is wrong, so it
        // should be converted to a NonstandardUuid.
        $expectedNonstandardV2 = [
            'type' => NonstandardUuid::class,
            'variant' => Variant::Rfc4122,
            'version' => Version::DceSecurity,
            'json' => '"0001e240-4103-21ed-80ff-0100499602d2"',
            'string' => '0001e240-4103-21ed-80ff-0100499602d2',
            'bytes' => "\x00\x01\xe2\x40\x41\x03\x21\xed\x80\xff\x01\x00\x49\x96\x02\xd2",
            'hex' => '0001e240410321ed80ff0100499602d2',
            'int' => '9781212151673653175651340685542098',
            'urn' => 'urn:uuid:0001e240-4103-21ed-80ff-0100499602d2',
            'node' => BadMethodCall::class,
            'date' => BadMethodCall::class,
        ];

        return [
            'max string' => ['value' => $expectedMax['string'], 'expected' => $expectedMax],
            'max bytes' => ['value' => $expectedMax['bytes'], 'expected' => $expectedMax],
            'max hex' => ['value' => $expectedMax['hex'], 'expected' => $expectedMax],
            'nil string' => ['value' => $expectedNil['string'], 'expected' => $expectedNil],
            'nil bytes' => ['value' => $expectedNil['bytes'], 'expected' => $expectedNil],
            'nil hex' => ['value' => $expectedNil['hex'], 'expected' => $expectedNil],
            'v1 string' => ['value' => $expectedV1['string'], 'expected' => $expectedV1],
            'v1 bytes' => ['value' => $expectedV1['bytes'], 'expected' => $expectedV1],
            'v1 hex' => ['value' => $expectedV1['hex'], 'expected' => $expectedV1],
            'v2 string' => ['value' => $expectedV2['string'], 'expected' => $expectedV2],
            'v2 bytes' => ['value' => $expectedV2['bytes'], 'expected' => $expectedV2],
            'v2 hex' => ['value' => $expectedV2['hex'], 'expected' => $expectedV2],
            'v3 string' => ['value' => $expectedV3['string'], 'expected' => $expectedV3],
            'v3 bytes' => ['value' => $expectedV3['bytes'], 'expected' => $expectedV3],
            'v3 hex' => ['value' => $expectedV3['hex'], 'expected' => $expectedV3],
            'v4 string' => ['value' => $expectedV4['string'], 'expected' => $expectedV4],
            'v4 bytes' => ['value' => $expectedV4['bytes'], 'expected' => $expectedV4],
            'v4 hex' => ['value' => $expectedV4['hex'], 'expected' => $expectedV4],
            'v5 string' => ['value' => $expectedV5['string'], 'expected' => $expectedV5],
            'v5 bytes' => ['value' => $expectedV5['bytes'], 'expected' => $expectedV5],
            'v5 hex' => ['value' => $expectedV5['hex'], 'expected' => $expectedV5],
            'v6 string' => ['value' => $expectedV6['string'], 'expected' => $expectedV6],
            'v6 bytes' => ['value' => $expectedV6['bytes'], 'expected' => $expectedV6],
            'v6 hex' => ['value' => $expectedV6['hex'], 'expected' => $expectedV6],
            'v7 string' => ['value' => $expectedV7['string'], 'expected' => $expectedV7],
            'v7 bytes' => ['value' => $expectedV7['bytes'], 'expected' => $expectedV7],
            'v7 hex' => ['value' => $expectedV7['hex'], 'expected' => $expectedV7],
            'v8 string' => ['value' => $expectedV8['string'], 'expected' => $expectedV8],
            'v8 bytes' => ['value' => $expectedV8['bytes'], 'expected' => $expectedV8],
            'v8 hex' => ['value' => $expectedV8['hex'], 'expected' => $expectedV8],
            'nonstandard string' => ['value' => $expectedNonstandard['string'], 'expected' => $expectedNonstandard],
            'nonstandard bytes' => ['value' => $expectedNonstandard['bytes'], 'expected' => $expectedNonstandard],
            'nonstandard hex' => ['value' => $expectedNonstandard['hex'], 'expected' => $expectedNonstandard],
            'nonstandard v2 string' => [
                'value' => $expectedNonstandardV2['string'],
                'expected' => $expectedNonstandardV2,
            ],
            'nonstandard v2 bytes' => [
                'value' => $expectedNonstandardV2['bytes'],
                'expected' => $expectedNonstandardV2,
            ],
            'nonstandard v2 hex' => ['value' => $expectedNonstandardV2['hex'], 'expected' => $expectedNonstandardV2],
        ];
    }

    public function testUntypedUuidThrowsExceptionForInvalidValue(): void
    {
        $this->expectException(InvalidArgument::class);
        $this->expectExceptionMessage('Invalid UUID: "foobar"');

        new UntypedUuid('foobar');
    }

    public function testGetDateTimeThrowsExceptionForNonTimeBasedUuid(): void
    {
        $uuid = new UntypedUuid('81daa361-8088-499a-adbd-71e4a45ae25c');

        $this->expectException(BadMethodCall::class);
        $this->expectExceptionMessage(
            'Cannot call getDateTime() on untyped UUID "81daa361-8088-499a-adbd-71e4a45ae25c"',
        );

        $uuid->getDateTime();
    }

    public function testGetNodeThrowsExceptionForNonNodeBasedUuid(): void
    {
        $uuid = new UntypedUuid('81daa361-8088-499a-adbd-71e4a45ae25c');

        $this->expectException(BadMethodCall::class);
        $this->expectExceptionMessage(
            'Cannot call getNode() on untyped UUID "81daa361-8088-499a-adbd-71e4a45ae25c"',
        );

        $uuid->getNode();
    }

    public function testGetVersionThrowsExceptionWhenUnableToDetermineVersion(): void
    {
        $uuid = new UntypedUuid('81daa361-8088-499a-edbd-71e4a45ae25c');

        $this->expectException(CannotDetermineVersion::class);
        $this->expectExceptionMessage(
            'Unable to determine version of untyped UUID "81daa361-8088-499a-edbd-71e4a45ae25c"',
        );

        $uuid->getVersion();
    }
}
