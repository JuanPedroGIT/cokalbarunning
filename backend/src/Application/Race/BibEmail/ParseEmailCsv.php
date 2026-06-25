<?php

declare(strict_types=1);

namespace App\Application\Race\BibEmail;

final class ParseEmailCsv
{
    /**
     * Parsea el CSV único de inscritos.
     * Cabeceras esperadas: Dorsal;Nombre;Apellidos;Sexo;email;NIF_Pasaporte;Fecha Nacimiento;Club;Talla Camiseta;Local;Año;Años;CATEGORIA
     */
    public function parse(string $csvContent): array
    {
        $csvContent = $this->removeBom($csvContent);
        $rows = $this->splitLines($csvContent);
        if ($rows === []) {
            return [];
        }

        $headers = $this->parseLine(array_shift($rows));
        if ($headers === [] || $headers === [null] || $headers === ['']) {
            return [];
        }

        $headerMap = [];
        foreach ($headers as $index => $header) {
            $headerMap[$index] = $this->normalizeHeader((string) $header);
        }

        $recipients = [];
        foreach ($rows as $row) {
            if (trim($row) === '') {
                continue;
            }

            $values = $this->parseLine($row);
            $data = [];
            foreach ($headerMap as $index => $normalized) {
                $data[$normalized] = $values[$index] ?? '';
            }

            $recipient = $this->parseRegistrationRow($data);
            if ($recipient !== null) {
                $recipients[] = $recipient;
            }
        }

        return $recipients;
    }

    /**
     * @param array<string, string> $data
     */
    private function parseRegistrationRow(array $data): ?EmailRecipientDto
    {
        $firstName = trim((string) ($data['nombre'] ?? ''));
        $lastName = trim((string) ($data['apellidos'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));
        $reference = trim((string) ($data['dorsal'] ?? ''));

        if ($firstName === '' || $email === '') {
            return null;
        }

        $gender = match (mb_strtolower(trim((string) ($data['sexo'] ?? '')))) {
            'mujer', 'femenino', 'f' => 'F',
            'hombre', 'masculino', 'm' => 'M',
            default => null,
        };

        $birthDate = null;
        $rawBirthDate = trim((string) ($data['fecha_nacimiento'] ?? ''));
        if ($rawBirthDate !== '') {
            $birthDate = $this->parseBirthDate($rawBirthDate);
        }

        return new EmailRecipientDto(
            firstName: $firstName,
            lastName: $lastName,
            email: $email,
            reference: $reference !== '' ? $reference : null,
            club: $this->nullableString($data['club'] ?? ''),
            gender: $gender,
            birthDate: $birthDate,
            category: $this->nullableString($data['categoria'] ?? ''),
            shirtSize: $this->nullableString($data['talla_camiseta'] ?? ''),
            emailValid: $this->isEmailValid($email),
        );
    }

    private function normalizeHeader(string $header): string
    {
        $normalized = mb_strtolower(trim($header));
        $normalized = str_replace([' ', '  '], '_', $normalized);
        $normalized = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ñ'], ['a', 'e', 'i', 'o', 'u', 'n'], $normalized);

        return $normalized;
    }

    private function nullableString(string $value): ?string
    {
        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }

    private function parseBirthDate(string $value): ?\DateTimeImmutable
    {
        $formats = ['d_m_Y', 'd-m-Y', 'd/m/Y', 'Y-m-d'];
        foreach ($formats as $format) {
            $date = \DateTimeImmutable::createFromFormat($format, $value);
            if ($date !== false) {
                return $date->setTime(0, 0, 0);
            }
        }

        return null;
    }

    /**
     * @return string[]
     */
    private function splitLines(string $content): array
    {
        return preg_split('/\r\n|\r|\n/', $content) ?: [];
    }

    /**
     * @return string[]
     */
    private function parseLine(string $line): array
    {
        return str_getcsv($line, ';', '"', '\\');
    }

    private function isEmailValid(string $email): bool
    {
        return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    private function removeBom(string $content): string
    {
        $bom = "\xEF\xBB\xBF";
        if (str_starts_with($content, $bom)) {
            return substr($content, 3);
        }

        return $content;
    }
}
