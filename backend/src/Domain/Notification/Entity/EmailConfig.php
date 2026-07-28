<?php

declare(strict_types=1);

namespace App\Domain\Notification\Entity;

use DateTimeImmutable;

final class EmailConfig
{
    public function __construct(
        private string $id,
        private string $raceEditionId,
        private string $type,
        private ?string $subject = null,
        private ?string $title = null,
        private ?string $description = null,
        private ?string $prize = null,
        private ?string $drawDate = null,
        private ?string $prizeImageUrl = null,
        private DateTimeImmutable $createdAt = new DateTimeImmutable(),
        private DateTimeImmutable $updatedAt = new DateTimeImmutable(),
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function raceEditionId(): string
    {
        return $this->raceEditionId;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function subject(): ?string
    {
        return $this->subject;
    }

    public function title(): ?string
    {
        return $this->title;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function prize(): ?string
    {
        return $this->prize;
    }

    public function drawDate(): ?string
    {
        return $this->drawDate;
    }

    public function prizeImageUrl(): ?string
    {
        return $this->prizeImageUrl;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /** @param array<string, mixed> $data */
    public function update(array $data): void
    {
        if (array_key_exists('subject', $data)) {
            $this->subject = $data['subject'] !== null && $data['subject'] !== '' ? (string) $data['subject'] : null;
        }
        if (array_key_exists('title', $data)) {
            $this->title = $data['title'] !== null && $data['title'] !== '' ? (string) $data['title'] : null;
        }
        if (array_key_exists('description', $data)) {
            $this->description = $data['description'] !== null && $data['description'] !== '' ? (string) $data['description'] : null;
        }
        if (array_key_exists('prize', $data)) {
            $this->prize = $data['prize'] !== null && $data['prize'] !== '' ? (string) $data['prize'] : null;
        }
        if (array_key_exists('drawDate', $data)) {
            $this->drawDate = $data['drawDate'] !== null && $data['drawDate'] !== '' ? (string) $data['drawDate'] : null;
        }
        if (array_key_exists('prizeImageUrl', $data)) {
            $newValue = $data['prizeImageUrl'] !== null && $data['prizeImageUrl'] !== '' ? (string) $data['prizeImageUrl'] : null;
            if ($newValue !== null && !str_starts_with($newValue, 'http://') && !str_starts_with($newValue, 'https://')) {
                $newValue = ltrim($newValue, '/');
            } elseif ($newValue !== null) {
                $newValue = ltrim(preg_replace('#^https?://[^/]+/#', '', $newValue) ?? $newValue, '/');
            }
            $this->prizeImageUrl = $newValue !== '' ? $newValue : null;
        }
        $this->updatedAt = new DateTimeImmutable();
    }

    public function setPrizeImageUrl(?string $prizeImageUrl): void
    {
        $this->prizeImageUrl = $prizeImageUrl;
        $this->updatedAt = new DateTimeImmutable();
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'raceEditionId' => $this->raceEditionId,
            'type' => $this->type,
            'subject' => $this->subject,
            'title' => $this->title,
            'description' => $this->description,
            'prize' => $this->prize,
            'drawDate' => $this->drawDate,
            'prizeImageUrl' => $this->prizeImageUrl,
            'createdAt' => $this->createdAt->format('Y-m-d H:i:s'),
            'updatedAt' => $this->updatedAt->format('Y-m-d H:i:s'),
        ];
    }
}
