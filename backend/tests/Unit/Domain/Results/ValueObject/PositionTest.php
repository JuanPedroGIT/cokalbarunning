<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Results\ValueObject;

use App\Domain\Results\ValueObject\Position;
use App\Domain\Shared\Exception\InvalidValueException;
use PHPUnit\Framework\TestCase;

final class PositionTest extends TestCase
{
    public function testValidPosition(): void
    {
        $position = new Position(1);
        $this->assertSame(1, $position->value());
    }

    public function testZeroPosition(): void
    {
        $this->expectException(InvalidValueException::class);
        new Position(0);
    }

    public function testNegativePosition(): void
    {
        $this->expectException(InvalidValueException::class);
        new Position(-5);
    }
}
