<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Registration\ValueObject;

use App\Domain\Registration\ValueObject\BibNumber;
use App\Domain\Shared\Exception\InvalidValueException;
use PHPUnit\Framework\TestCase;

final class BibNumberTest extends TestCase
{
    public function testValidBib(): void
    {
        $bib = BibNumber::fromString('123');
        $this->assertSame('123', $bib->value());
    }

    public function testBibWithDash(): void
    {
        $bib = BibNumber::fromString('A-123');
        $this->assertSame('A-123', $bib->value());
    }

    public function testEmptyBib(): void
    {
        $this->expectException(InvalidValueException::class);
        BibNumber::fromString('');
    }

    public function testWhitespaceBib(): void
    {
        $this->expectException(InvalidValueException::class);
        BibNumber::fromString('   ');
    }

    public function testInvalidCharacters(): void
    {
        $this->expectException(InvalidValueException::class);
        BibNumber::fromString('123!');
    }
}
