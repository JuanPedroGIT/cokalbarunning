<?php

declare(strict_types=1);

namespace App\Application\Race\BibEmail;

final readonly class BibEmailRecipientDto
{
    public function __construct(
        public string $name,
        public string $email,
        public string $bibNumber,
        public bool $emailValid,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: (string) ($data['name'] ?? ''),
            email: (string) ($data['email'] ?? ''),
            bibNumber: (string) ($data['bibNumber'] ?? ''),
            emailValid: (bool) ($data['emailValid'] ?? false),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'bibNumber' => $this->bibNumber,
            'emailValid' => $this->emailValid,
        ];
    }
}
