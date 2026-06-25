<?php

declare(strict_types=1);

namespace App\Domain\Notification\Entity;

use App\Domain\Notification\ValueObject\EmailStatus;
use App\Domain\Notification\ValueObject\EmailType;

final class EmailSendLog
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        private string $id,
        private EmailType $type,
        private string $recipientEmail,
        private string $recipientName,
        private ?string $reference,
        private EmailStatus $status,
        private ?string $raceEditionId = null,
        private ?string $errorMessage = null,
        private ?\DateTimeImmutable $sentAt = null,
        private ?string $sentBy = null,
        private ?\DateTimeImmutable $createdAt = null,
        private ?\DateTimeImmutable $updatedAt = null,
        private array $metadata = [],
    ) {
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public static function create(
        string $id,
        string $type,
        string $recipientEmail,
        string $recipientName,
        ?string $reference = null,
        ?string $raceEditionId = null,
        array $metadata = [],
    ): self {
        $now = new \DateTimeImmutable();

        return new self(
            id: $id,
            type: new EmailType($type),
            recipientEmail: $recipientEmail,
            recipientName: $recipientName,
            reference: $reference,
            raceEditionId: $raceEditionId,
            status: EmailStatus::pending(),
            createdAt: $now,
            updatedAt: $now,
            metadata: $metadata,
        );
    }

    public function id(): string
    {
        return $this->id;
    }

    public function type(): EmailType
    {
        return $this->type;
    }

    public function recipientEmail(): string
    {
        return $this->recipientEmail;
    }

    public function recipientName(): string
    {
        return $this->recipientName;
    }

    public function reference(): ?string
    {
        return $this->reference;
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

    /**
     * @return array<string, mixed>
     */
    public function metadata(): array
    {
        return $this->metadata;
    }

    public function createdAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function assignSentBy(string $userId): void
    {
        $this->sentBy = $userId;
    }

    public function markAsPending(): void
    {
        $this->status = EmailStatus::pending();
        $this->updatedAt = new \DateTimeImmutable();
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
