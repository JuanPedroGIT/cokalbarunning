<?php

declare(strict_types=1);

namespace App\Domain\Notification\Entity;

use App\Domain\Notification\ValueObject\EmailStatus;

final class EmailSendLog
{
    public function __construct(
        private string $id,
        private string $recipientEmail,
        private string $recipientName,
        private string $bibNumber,
        private EmailStatus $status,
        private ?string $raceEditionId = null,
        private ?string $errorMessage = null,
        private ?\DateTimeImmutable $sentAt = null,
        private ?string $sentBy = null,
        private ?\DateTimeImmutable $createdAt = null,
        private ?\DateTimeImmutable $updatedAt = null,
    ) {
    }

    public static function create(
        string $id,
        string $recipientEmail,
        string $recipientName,
        string $bibNumber,
        ?string $raceEditionId = null,
    ): self {
        $now = new \DateTimeImmutable();

        return new self(
            id: $id,
            recipientEmail: $recipientEmail,
            recipientName: $recipientName,
            bibNumber: $bibNumber,
            raceEditionId: $raceEditionId,
            status: EmailStatus::pending(),
            createdAt: $now,
            updatedAt: $now,
        );
    }

    public function id(): string
    {
        return $this->id;
    }

    public function recipientEmail(): string
    {
        return $this->recipientEmail;
    }

    public function recipientName(): string
    {
        return $this->recipientName;
    }

    public function bibNumber(): string
    {
        return $this->bibNumber;
    }

    public function raceEditionId(): ?string
    {
        return $this->raceEditionId;
    }

    public function status(): EmailStatus
    {
        return $this->status;
    }

    public function errorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function sentAt(): ?\DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function sentBy(): ?string
    {
        return $this->sentBy;
    }

    public function assignSentBy(string $userId): void
    {
        $this->sentBy = $userId;
    }

    public function createdAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function markAsSent(): void
    {
        $this->status = EmailStatus::sent();
        $this->sentAt = new \DateTimeImmutable();
        $this->errorMessage = null;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function markAsSentBy(string $userId): void
    {
        $this->markAsSent();
        $this->sentBy = $userId;
    }

    public function markAsError(string $message): void
    {
        $this->status = EmailStatus::error();
        $this->errorMessage = $message;
        $this->updatedAt = new \DateTimeImmutable();
    }
}
