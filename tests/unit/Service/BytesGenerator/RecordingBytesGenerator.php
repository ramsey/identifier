<?php

declare(strict_types=1);

namespace Ramsey\Test\Identifier\Service\BytesGenerator;

use DateTimeInterface;
use Ramsey\Identifier\Service\BytesGenerator\BytesGenerator;

use function str_repeat;

/**
 * A bytes generator that records the lengths it is asked for and returns
 * chosen bytes for chosen lengths
 */
final class RecordingBytesGenerator implements BytesGenerator
{
    /**
     * @var list<int>
     */
    public array $requests = [];

    /**
     * @param array<int, non-empty-string> $results The bytes to return for a
     *     length; a length that is not listed gets that many zero bytes
     */
    public function __construct(private readonly array $results = [])
    {
    }

    public function bytes(int $length = 16, ?DateTimeInterface $dateTime = null): string
    {
        $this->requests[] = $length;

        return $this->results[$length] ?? str_repeat("\x00", $length);
    }
}
