<?php

declare(strict_types=1);

namespace App\Domain\Registration\Entity;

use DateTimeImmutable;

final class Runner
{
    public function __construct(
        private string $id,
        private string $firstName,
        private string $lastName,
        private ?string $email = null,
        private ?string $raceEditionId = null,
        private ?string $bibNumber = null,
        private ?string $club = null,
        private ?DateTimeImmutable $birthDate = null,
        private ?string $gender = null,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function firstName(): string
    {
        return $this->firstName;
    }

    public function lastName(): string
    {
        return $this->lastName;
    }

    public function fullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    public function email(): ?string
    {
        return $this->email;
    }

    public function raceEditionId(): ?string
    {
        return $this->raceEditionId;
    }

    public function bibNumber(): ?string
    {
        return $this->bibNumber;
    }

    public function club(): ?string
    {
        return $this->club;
    }

    public function birthDate(): ?DateTimeImmutable
    {
        return $this->birthDate;
    }

    public function gender(): ?string
    {
        return $this->gender;
    }

    public function ageAt(DateTimeImmutable $date): ?int
    {
        if ($this->birthDate === null) {
            return null;
        }

        return $date->diff($this->birthDate)->y;
    }

    public function update(string $firstName, string $lastName, ?string $email, ?string $club, ?DateTimeImmutable $birthDate, ?string $gender): void
    {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->email = $email;
        $this->club = $club;
        $this->birthDate = $birthDate;
        $this->gender = $gender;
    }

    public function assignEditionAndBib(string $raceEditionId, string $bibNumber): void
    {
        $this->raceEditionId = $raceEditionId;
        $this->bibNumber = $bibNumber;
    }
}
