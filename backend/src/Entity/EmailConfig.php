<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'emails_config')]
#[ORM\UniqueConstraint(columns: ['race_edition_id', 'type'], name: 'idx_emails_config_edition_type')]
class EmailConfig
{
    #[ORM\Id]
    #[ORM\Column(type: Types::GUID)]
    private string $id;

    #[ORM\Column(type: Types::GUID)]
    private string $raceEditionId;

    #[ORM\Column(length: 20)]
    private string $type = 'raffle';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $subject = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $prize = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $drawDate = null;

    #[ORM\Column(length: 512, nullable: true)]
    private ?string $prizeImageUrl = null;

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

    public function getRaceEditionId(): string
    {
        return $this->raceEditionId;
    }

    public function setRaceEditionId(string $raceEditionId): static
    {
        $this->raceEditionId = $raceEditionId;
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

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(?string $subject): static
    {
        $this->subject = $subject;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getPrize(): ?string
    {
        return $this->prize;
    }

    public function setPrize(?string $prize): static
    {
        $this->prize = $prize;
        return $this;
    }

    public function getDrawDate(): ?string
    {
        return $this->drawDate;
    }

    public function setDrawDate(?string $drawDate): static
    {
        $this->drawDate = $drawDate;
        return $this;
    }

    public function getPrizeImageUrl(): ?string
    {
        return $this->prizeImageUrl;
    }

    public function setPrizeImageUrl(?string $prizeImageUrl): static
    {
        $this->prizeImageUrl = $prizeImageUrl;
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

    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * @return array<string, mixed>
     */
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
