<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Service\BytesGenerator;

use DateTimeImmutable;
use Ramsey\Identifier\Service\BytesGenerator\FixedBytesGenerator;
use Ramsey\Identifier\Service\BytesGenerator\MonotonicBytesGenerator;
use Ramsey\Identifier\Service\Clock\FrozenClock;
use Ramsey\Test\Identifier\TestCase;

use function strlen;
use function substr;

class MonotonicBytesGeneratorTest extends TestCase
{
    /**
     * @runInSeparateProcess since values are stored statically on the class
     * @preserveGlobalState disabled
     */
    public function testBytesWithFactoryInitializedValues(): void
    {
        $bytesGenerator = new MonotonicBytesGenerator(
            new FixedBytesGenerator("\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00"),
            new FrozenClock(new DateTimeImmutable('1970-01-01 00:00:00.000000')),
        );

        $bytes = $bytesGenerator->bytes();

        $this->assertSame("\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00", $bytes);

        // Another group of bytes generated will not be identical because of the
        // non-deterministic randomizing we perform inside the class.
        $bytesNext = $bytesGenerator->bytes();

        $this->assertSame(16, strlen($bytesNext));
        $this->assertTrue($bytes < $bytesNext);
    }

    public function testBytesWithDateTime(): void
    {
        $dateTime = new DateTimeImmutable('2022-09-25 17:32:12.123456');
        $bytesGenerator = new MonotonicBytesGenerator();
        $bytes = $bytesGenerator->bytes(dateTime: $dateTime);

        $this->assertSame("\x01\x83\x75\xb4\xe1\xdb", substr($bytes, 0, 6));
    }

    public function testBytesAreMonotonicallyIncreasing(): void
    {
        $bytesGenerator = new MonotonicBytesGenerator();

        $previous = $bytesGenerator->bytes();

        for ($i = 0; $i < 25; $i++) {
            $bytes = $bytesGenerator->bytes();
            $this->assertTrue($previous < $bytes);
            $previous = $bytes;
        }
    }

    public function testLongerLengthBytesRequestedAreMonotonicallyIncreasing(): void
    {
        $bytesGenerator = new MonotonicBytesGenerator();

        $previous = $bytesGenerator->bytes(29);
        $this->assertSame(29, strlen($previous));

        for ($i = 0; $i < 25; $i++) {
            $bytes = $bytesGenerator->bytes(29);
            $this->assertSame(29, strlen($bytes));
            $this->assertTrue($previous < $bytes);
            $previous = $bytes;
        }
    }

    /**
     * We support fewer bytes returned, but when you truncate the bytes, you
     * lose the monotonicity, since the monotonicity is based on a 48-bit
     * timestamp.
     */
    public function testShorterLengthBytesRequested(): void
    {
        $bytesGenerator = new MonotonicBytesGenerator();

        $this->assertSame(5, strlen($bytesGenerator->bytes(5)));
    }

    /**
     * @runInSeparateProcess since values are stored statically on the class
     * @preserveGlobalState disabled
     */
    public function testBytesWithMaximumRandomSeedValue(): void
    {
        $bytesGenerator = new MonotonicBytesGenerator(
            bytesGenerator: new FixedBytesGenerator(
                "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff",
            ),
        );

        $previous = $bytesGenerator->bytes();

        for ($i = 0; $i < 25; $i++) {
            $bytes = $bytesGenerator->bytes();
            $this->assertTrue($previous < $bytes);
            $previous = $bytes;
        }
    }

    /**
     * @runInSeparateProcess since values are stored statically on the class
     * @preserveGlobalState disabled
     */
    public function testBytesWithMaximumRandomSeedValueWithTimeAtMaximumNines(): void
    {
        $date = new DateTimeImmutable('@1666999999.999');

        $bytesGenerator = new MonotonicBytesGenerator(
            bytesGenerator: new FixedBytesGenerator(
                "\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff",
            ),
            clock: new FrozenClock($date),
        );

        $previous = $bytesGenerator->bytes();

        for ($i = 0; $i < 25; $i++) {
            $bytes = $bytesGenerator->bytes();
            $this->assertTrue($previous < $bytes);
            $previous = $bytes;
        }
    }
}
