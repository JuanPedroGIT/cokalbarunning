<?php

declare(strict_types=1);

namespace App\Application\Race\BibEmail;

final readonly class EmailRecipientDto
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $email,
        public ?string $reference = null,
        public ?string $club = null,
        public ?string $gender = null,
        public ?\DateTimeImmutable $birthDate = null,
        public ?string $category = null,
        public ?string $shirtSize = null,
        public bool $emailValid = false,
    ) {
    }

    public function fullName(): string
    {
        $full = trim($this->firstName . ' ' . $this->lastName);

        return $full !== '' ? $full : $this->email;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $birthDate = null;
        $rawBirthDate = $data['birthDate'] ?? null;
        if (\is_string($rawBirthDate) && $rawBirthDate !== '') {
            $birthDate = self::parseBirthDate($rawBirthDate);
        }

        return new self(
            firstName: (string) ($data['firstName'] ?? ''),
            lastName: (string) ($data['lastName'] ?? ''),
            email: (string) ($data['email'] ?? ''),
            reference: isset($data['reference']) ? (string) $data['reference'] : null,
            club: isset($data['club']) ? (string) $data['club'] : null,
            gender: isset($data['gender']) ? (string) $data['gender'] : null,
            birthDate: $birthDate,
            category: isset($data['category']) ? (string) $data['category'] : null,
            shirtSize: isset($data['shirtSize']) ? (string) $data['shirtSize'] : null,
            emailValid: (bool) ($data['emailValid'] ?? false),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'fullName' => $this->fullName(),
            'email' => $this->email,
            'reference' => $this->reference,
            'club' => $this->club,
            'gender' => $this->gender,
            'birthDate' => $this->birthDate?->format('Y-m-d'),
            'category' => $this->category,
            'shirtSize' => $this->shirtSize,
            'emailValid' => $this->emailValid,
        ];
    }

    private static function parseBirthDate(string $value): ?\DateTimeImmutable
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
}
