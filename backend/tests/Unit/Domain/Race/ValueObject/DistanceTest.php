<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Race\ValueObject;

use App\Domain\Race\ValueObject\Distance;
use App\Domain\Shared\Exception\InvalidValueException;
use PHPUnit\Framework\TestCase;

final class DistanceTest extends TestCase
{
    public function testValidDistance(): void
    {
        $distance = Distance::fromKilometers(10.5);
        $this->assertSame(10.5, $distance->kilometers());
    }

    public function testMetersConversion(): void
    {
        $distance = Distance::fromKilometers(5.0);
        $this->assertSame(5000, $distance->meters());
    }

    public function testZeroDistance(): void
    {
        $this->expectException(InvalidValueException::class);
        Distance::fromKilometers(0);
    }

    public function testNegativeDistance(): void
    {
        $this->expectException(InvalidValueException::class);
        Distance::fromKilometers(-1);
    }

    public function testFormatKilometers(): void
    {
        $distance = Distance::fromKilometers(10.5);
        $this->assertSame('10.5 km', $distance->format());
    }
}
