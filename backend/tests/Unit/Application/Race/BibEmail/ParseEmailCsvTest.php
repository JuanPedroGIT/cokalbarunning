<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Race\BibEmail;

use App\Application\Race\BibEmail\ParseEmailCsv;
use PHPUnit\Framework\TestCase;

final class ParseEmailCsvTest extends TestCase
{
    public function testParseRegistrationCsv(): void
    {
        $parser = new ParseEmailCsv();
        $csv = "Dorsal;Nombre;Apellidos;Sexo;email;NIF_Pasaporte;Fecha Nacimiento;Club;Talla Camiseta;Local;Año;Años;CATEGORIA\n"
            . "1;Juan;Pérez López;HOMBRE;juan@example.com;12345678A;15_05_1985;Cokalba Running;M;SI;1985;40;VETERANO A\n"
            . "2;Ana;García Ruiz;MUJER;ana@example.com;87654321B;20_03_1990;;S;NO;1990;36;VETERANO A";

        $recipients = $parser->parse($csv);

        self::assertCount(2, $recipients);
        self::assertSame('Juan', $recipients[0]->firstName);
        self::assertSame('Pérez López', $recipients[0]->lastName);
        self::assertSame('Juan Pérez López', $recipients[0]->fullName());
        self::assertSame('juan@example.com', $recipients[0]->email);
        self::assertSame('1', $recipients[0]->reference);
        self::assertSame('Cokalba Running', $recipients[0]->club);
        self::assertSame('M', $recipients[0]->gender);
        self::assertSame('M', $recipients[0]->shirtSize);
        self::assertSame('VETERANO A', $recipients[0]->category);
        self::assertTrue($recipients[0]->emailValid);
        self::assertSame('ana@example.com', $recipients[1]->email);
        self::assertNull($recipients[1]->club);
    }

    public function testParseInvalidEmail(): void
    {
        $parser = new ParseEmailCsv();
        $csv = "Dorsal;Nombre;Apellidos;Sexo;email\n1;Juan;Pérez;HOMBRE;not-an-email";

        $recipients = $parser->parse($csv);

        self::assertCount(1, $recipients);
        self::assertFalse($recipients[0]->emailValid);
    }

    public function testParseEmptyLinesAreIgnored(): void
    {
        $parser = new ParseEmailCsv();
        $csv = "Dorsal;Nombre;Apellidos;email\n\n1;Juan;Pérez;juan@example.com\n\n";

        $recipients = $parser->parse($csv);

        self::assertCount(1, $recipients);
    }

    public function testParseEmptyCsvReturnsEmptyArray(): void
    {
        $parser = new ParseEmailCsv();

        self::assertSame([], $parser->parse(''));
    }
}
