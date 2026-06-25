<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'email_send_logs')]
#[ORM\Index(columns: ['race_edition_id'], name: 'idx_email_send_log_edition')]
#[ORM\Index(columns: ['recipient_email'], name: 'idx_email_send_log_email')]
#[ORM\Index(columns: ['status'], name: 'idx_email_send_log_status')]
#[ORM\Index(columns: ['type'], name: 'idx_email_send_log_type')]
class EmailSendLog
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private string $id;

    #[ORM\Column(type: 'string', length: 20)]
    private string $type = 'bib';

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $raceEditionId = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $recipientEmail;

    #[ORM\Column(type: 'string', length: 255)]
    private string $recipientName;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $reference = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'pending';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $errorMessage = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $sentAt = null;

    #[ORM\Column(type: 'string', length: 36, nullable: true)]
    private ?string $sentBy = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): static
    {
        $this->id = $id;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getRaceEditionId(): ?string
    {
        return $this->raceEditionId;
    }

    public function setRaceEditionId(?string $raceEditionId): static
    {
        $this->raceEditionId = $raceEditionId;
        return $this;
    }

    public function getRecipientEmail(): string
    {
        return $this->recipientEmail;
    }

    public function setRecipientEmail(string $recipientEmail): static
    {
        $this->recipientEmail = $recipientEmail;
        return $this;
    }

    public function getRecipientName(): string
    {
        return $this->recipientName;
    }

    public function setRecipientName(string $recipientName): static
    {
        $this->recipientName = $recipientName;
        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(?string $reference): static
    {
        $this->reference = $reference;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    public function setErrorMessage(?string $errorMessage): static
    {
        $this->errorMessage = $errorMessage;
        return $this;
    }

    public function getSentAt(): ?\DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function setSentAt(?\DateTimeImmutable $sentAt): static
    {
        $this->sentAt = $sentAt;
        return $this;
    }

    public function getSentBy(): ?string
    {
        return $this->sentBy;
    }

    public function setSentBy(?string $sentBy): static
    {
        $this->sentBy = $sentBy;
        return $this;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata): static
    {
        $this->metadata = $metadata;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function markAsSent(): static
    {
        $this->status = 'sent';
        $this->sentAt = new \DateTimeImmutable();
        $this->errorMessage = null;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }

    public function markAsError(string $message): static
    {
        $this->status = 'error';
        $this->errorMessage = $message;
        $this->updatedAt = new \DateTimeImmutable();
        return $this;
    }
}
