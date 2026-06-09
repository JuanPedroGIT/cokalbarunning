<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SocialPublishLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SocialPublishLogRepository::class)]
#[ORM\Table(name: 'social_publish_logs')]
#[ORM\UniqueConstraint(name: 'uniq_post_network', columns: ['post_id', 'network'])]
#[ORM\Index(name: 'idx_status', columns: ['status'])]
class SocialPublishLog
{
    #[ORM\Id]
    #[ORM\Column(type: Types::GUID)]
    private string $id;

    #[ORM\Column(type: Types::GUID)]
    private string $postId;

    #[ORM\Column(length: 50)]
    private string $network;

    #[ORM\Column(length: 20)]
    private string $status;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $publishedAt = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $externalUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $publishedBy = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
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

    public function getPostId(): string
    {
        return $this->postId;
    }

    public function setPostId(string $postId): static
    {
        $this->postId = $postId;
        return $this;
    }

    public function getNetwork(): string
    {
        return $this->network;
    }

    public function setNetwork(string $network): static
    {
        $this->network = $network;
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

    public function getPublishedAt(): ?\DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(?\DateTimeImmutable $publishedAt): static
    {
        $this->publishedAt = $publishedAt;
        return $this;
    }

    public function getExternalUrl(): ?string
    {
        return $this->externalUrl;
    }

    public function setExternalUrl(?string $externalUrl): static
    {
        $this->externalUrl = $externalUrl;
        return $this;
    }

    public function getPublishedBy(): ?string
    {
        return $this->publishedBy;
    }

    public function setPublishedBy(?string $publishedBy): static
    {
        $this->publishedBy = $publishedBy;
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
}
