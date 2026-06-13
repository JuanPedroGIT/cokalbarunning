<?php

declare(strict_types=1);

namespace App\Application\Race\BibEmail;

final class ParseBibEmailCsv
{
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
        $headerMap = array_map(fn (?string $h): string => strtolower((string) $h), $headers);

        $recipients = [];
        foreach ($rows as $row) {
            if (trim($row) === '') {
                continue;
            }

            $values = $this->parseLine($row);
            if (\count($values) !== \count($headers)) {
                continue;
            }

            $data = array_combine($headerMap, $values);
            if ($data === false) {
                continue;
            }

            $name = trim((string) ($data['nombre'] ?? ''));
            $email = trim((string) ($data['email'] ?? ''));
            $bibNumber = trim((string) ($data['dorsal'] ?? ''));

            if ($name === '' || $email === '' || $bibNumber === '') {
                continue;
            }

            $recipients[] = new BibEmailRecipientDto(
                name: $name,
                email: $email,
                bibNumber: $bibNumber,
                emailValid: $this->isEmailValid($email),
            );
        }

        return $recipients;
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
