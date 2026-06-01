<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Results\ValueObject;

use App\Domain\Results\ValueObject\FinishTime;
use PHPUnit\Framework\TestCase;

final class FinishTimeTest extends TestCase
{
    public function testFromSeconds(): void
    {
        $time = FinishTime::fromSeconds(3661);
        $this->assertSame(3661, $time->seconds());
        $this->assertSame('01:01:01', $time->format());
    }

    public function testFromString(): void
    {
        $time = FinishTime::fromString('02:30:45');
        $this->assertSame(9045, $time->seconds());
        $this->assertSame('02:30:45', $time->format());
    }

    public function testFormattedStringLessThanHour(): void
    {
        $time = FinishTime::fromSeconds(1234);
        $this->assertSame('00:20:34', $time->format());
    }

    public function testZeroSeconds(): void
    {
        $time = FinishTime::fromSeconds(0);
        $this->assertSame('00:00:00', $time->format());
    }

    public function testNegativeSecondsThrows(): void
    {
        $this->expectException(\App\Domain\Shared\Exception\InvalidValueException::class);
        FinishTime::fromSeconds(-1);
    }
}
