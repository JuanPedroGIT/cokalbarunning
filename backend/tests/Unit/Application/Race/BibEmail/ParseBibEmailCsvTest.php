<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Race\BibEmail;

use App\Application\Race\BibEmail\ParseBibEmailCsv;
use PHPUnit\Framework\TestCase;

final class ParseBibEmailCsvTest extends TestCase
{
    public function testParseValidCsv(): void
    {
        $parser = new ParseBibEmailCsv();
        $csv = "nombre;email;dorsal\nJuan Pérez;juan@example.com;001\nAna García;ana@example.com;002";

        $recipients = $parser->parse($csv);

        self::assertCount(2, $recipients);
        self::assertSame('Juan Pérez', $recipients[0]->name);
        self::assertSame('juan@example.com', $recipients[0]->email);
        self::assertSame('001', $recipients[0]->bibNumber);
        self::assertTrue($recipients[0]->emailValid);
        self::assertTrue($recipients[1]->emailValid);
        self::assertSame('ana@example.com', $recipients[1]->email);
    }

    public function testParseInvalidEmail(): void
    {
        $parser = new ParseBibEmailCsv();
        $csv = "nombre;email;dorsal\nJuan Pérez;not-an-email;001";

        $recipients = $parser->parse($csv);

        self::assertCount(1, $recipients);
        self::assertFalse($recipients[0]->emailValid);
    }

    public function testParseEmptyLinesAreIgnored(): void
    {
        $parser = new ParseBibEmailCsv();
        $csv = "nombre;email;dorsal\n\nJuan Pérez;juan@example.com;001\n\n";

        $recipients = $parser->parse($csv);

        self::assertCount(1, $recipients);
    }

    public function testParseEmptyCsvReturnsEmptyArray(): void
    {
        $parser = new ParseBibEmailCsv();

        self::assertSame([], $parser->parse(''));
    }
}
