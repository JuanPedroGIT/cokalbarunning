<?php

declare(strict_types=1);

namespace App\Domain\SocialPublishing\Entity;

use DateTimeImmutable;

final class SocialPublishLog
{
    public function __construct(
        private string $id,
        private string $postId,
        private string $network,
        private string $status,
        private DateTimeImmutable $createdAt,
        private ?DateTimeImmutable $publishedAt = null,
        private ?string $externalUrl = null,
        private ?string $publishedBy = null,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function postId(): string
    {
        return $this->postId;
    }

    public function network(): string
    {
        return $this->network;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function publishedAt(): ?DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function externalUrl(): ?string
    {
        return $this->externalUrl;
    }

    public function publishedBy(): ?string
    {
        return $this->publishedBy;
    }

    public function markAsPublished(?string $externalUrl = null): void
    {
        $this->status = 'published';
        $this->publishedAt = new DateTimeImmutable();
        $this->externalUrl = $externalUrl;
    }

    public function markAsFailed(): void
    {
        $this->status = 'failed';
    }

    public function resetToPending(): void
    {
        $this->status = 'pending';
        $this->publishedAt = null;
        $this->externalUrl = null;
    }

    public function updateStatus(string $status, ?string $externalUrl = null): void
    {
        $this->status = $status;
        if ($status === 'published') {
            $this->publishedAt = new DateTimeImmutable();
        }
        if ($externalUrl !== null) {
            $this->externalUrl = $externalUrl;
        }
    }
}
