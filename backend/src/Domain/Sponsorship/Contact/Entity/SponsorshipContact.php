<?php

declare(strict_types=1);

namespace App\Domain\Sponsorship\Contact\Entity;

final class SponsorshipContact
{
    public function __construct(
        private string $id,
        private string $name,
        private string $company,
        private string $email,
        private string $phone,
        private ?string $message = null,
        private \DateTimeImmutable $createdAt = new \DateTimeImmutable(),
    ) {
    }

    public function id(): string { return $this->id; }
    public function name(): string { return $this->name; }
    public function company(): string { return $this->company; }
    public function email(): string { return $this->email; }
    public function phone(): string { return $this->phone; }
    public function message(): ?string { return $this->message; }
    public function createdAt(): \DateTimeImmutable { return $this->createdAt; }
}
