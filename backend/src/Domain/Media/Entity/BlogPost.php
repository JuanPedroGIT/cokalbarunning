<?php

declare(strict_types=1);

namespace App\Domain\Media\Entity;

use DateTimeImmutable;

final class BlogPost
{
    public const TYPE_NEWS = 1;
    public const TYPE_BANNER = 2;
    public const TYPE_RACE = 3;
    public const TYPE_CLUB = 4;
    public const TYPE_OTHER = 5;

    public const VALID_TYPES = [
        self::TYPE_NEWS,
        self::TYPE_BANNER,
        self::TYPE_RACE,
        self::TYPE_CLUB,
        self::TYPE_OTHER,
    ];

    public function __construct(
        private string $id,
        private string $title,
        private string $slug,
        private string $excerpt,
        private string $content,
        private string $tag,
        private DateTimeImmutable $createdAt,
        private ?DateTimeImmutable $publishedAt = null,
        private ?DateTimeImmutable $bannerEndAt = null,
        private ?string $coverImage = null,
        private ?int $priority = null,
        private int $type = self::TYPE_NEWS,
    ) {
    }

    public function id(): string
    {
        return $this->id;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function slug(): string
    {
        return $this->slug;
    }

    public function excerpt(): string
    {
        return $this->excerpt;
    }

    public function content(): string
    {
        return $this->content;
    }

    public function tag(): string
    {
        return $this->tag;
    }

    public function publishedAt(): ?DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function bannerEndAt(): ?DateTimeImmutable
    {
        return $this->bannerEndAt;
    }

    public function coverImage(): ?string
    {
        return $this->coverImage;
    }

    public function priority(): ?int
    {
        return $this->priority;
    }

    public function type(): int
    {
        return $this->type;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isPublished(): bool
    {
        if ($this->publishedAt === null) {
            return false;
        }

        $now = new DateTimeImmutable();
        if ($this->publishedAt > $now) {
            return false;
        }

        if ($this->bannerEndAt !== null && $this->bannerEndAt < $now) {
            return false;
        }

        return true;
    }

    public function publish(): void
    {
        $this->publishedAt = new DateTimeImmutable();
    }

    public function unpublish(): void
    {
        $this->publishedAt = null;
    }

    public function updatePublishedAt(?DateTimeImmutable $publishedAt): void
    {
        $this->publishedAt = $publishedAt;
    }

    public function updateBannerEndAt(?DateTimeImmutable $bannerEndAt): void
    {
        $this->bannerEndAt = $bannerEndAt;
    }

    public function update(string $title, string $slug, string $excerpt, string $content, string $tag, ?string $coverImage): void
    {
        $this->title = $title;
        $this->slug = $slug;
        $this->excerpt = $excerpt;
        $this->content = $content;
        $this->tag = $tag;
        $this->coverImage = $coverImage;
    }

    public function updateType(int $type): void
    {
        $this->type = $type;
    }

    public function updatePriority(?int $priority): void
    {
        $this->priority = $priority;
    }
}
