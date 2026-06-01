<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Race\ValueObject;

use App\Domain\Race\ValueObject\DocumentType;
use App\Domain\Shared\Exception\InvalidValueException;
use PHPUnit\Framework\TestCase;

final class DocumentTypeTest extends TestCase
{
    public function testValidTypes(): void
    {
        $this->assertSame('route', (new DocumentType('route'))->value());
        $this->assertSame('profile', (new DocumentType('profile'))->value());
        $this->assertSame('results', (new DocumentType('results'))->value());
        $this->assertSame('general', (new DocumentType('general'))->value());
        $this->assertSame('other', (new DocumentType('other'))->value());
    }

    public function testInvalidTypeThrowsException(): void
    {
        $this->expectException(InvalidValueException::class);
        new DocumentType('invalid');
    }

    public function testEquals(): void
    {
        $a = new DocumentType('route');
        $b = new DocumentType('route');
        $c = new DocumentType('general');

        $this->assertTrue($a->equals($b));
        $this->assertFalse($a->equals($c));
    }
}
