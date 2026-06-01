<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Race\ValueObject;

use App\Domain\Race\ValueObject\EditionYear;
use App\Domain\Shared\Exception\InvalidValueException;
use PHPUnit\Framework\TestCase;

final class EditionYearTest extends TestCase
{
    public function testValidYear(): void
    {
        $year = new EditionYear(2026);
        $this->assertSame(2026, $year->value());
    }

    public function testYearTooLow(): void
    {
        $this->expectException(InvalidValueException::class);
        new EditionYear(1999);
    }

    public function testYearTooHigh(): void
    {
        $this->expectException(InvalidValueException::class);
        new EditionYear(2101);
    }
}
