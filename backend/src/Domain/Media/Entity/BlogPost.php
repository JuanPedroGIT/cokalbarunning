<?php

declare(strict_types=1);

namespace App\Domain\Media\Entity;

use DateTimeImmutable;

final class BlogPost
{
    public function __construct(
        private string $id,
        private string $title,
        private string $slug,
        private string $excerpt,
        private string $content,
        private string $tag,
        private DateTimeImmutable $createdAt,
        private ?DateTimeImmutable $publishedAt = null,
        private ?string $coverImage = null,
        private ?int $priority = null,
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

    public function coverImage(): ?string
    {
        return $this->coverImage;
    }

    public function priority(): ?int
    {
        return $this->priority;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isPublished(): bool
    {
        return $this->publishedAt !== null && $this->publishedAt <= new DateTimeImmutable();
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

    public function update(string $title, string $slug, string $excerpt, string $content, string $tag, ?string $coverImage): void
    {
        $this->title = $title;
        $this->slug = $slug;
        $this->excerpt = $excerpt;
        $this->content = $content;
        $this->tag = $tag;
        $this->coverImage = $coverImage;
    }

    public function updatePriority(?int $priority): void
    {
        $this->priority = $priority;
    }
}
