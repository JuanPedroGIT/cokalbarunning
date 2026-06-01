<?php

declare(strict_types=1);

namespace App\Application\Media\Response;

use App\Domain\Media\Entity\BlogPost;
use App\Domain\Media\Port\StoragePort;

final readonly class BlogPostResponseDto
{
    public function __construct(
        public string $id,
        public string $title,
        public string $slug,
        public string $excerpt,
        public string $tag,
        public ?string $publishedAt,
        public ?string $coverImage,
        public ?string $createdAt = null,
        public ?string $content = null,
        public ?bool $isPublished = null,
    ) {
    }

    private static function buildUrl(?string $path, StoragePort $storage): ?string
    {
        if ($path === null) return null;
        if (str_starts_with($path, 'http')) return $path;
        return $storage->url($path);
    }

    public static function fromDomain(BlogPost $post, StoragePort $storage): self
    {
        return new self(
            id: $post->id(),
            title: $post->title(),
            slug: $post->slug(),
            excerpt: $post->excerpt(),
            tag: $post->tag(),
            publishedAt: $post->publishedAt()?->format('Y-m-d'),
            coverImage: self::buildUrl($post->coverImage(), $storage),
        );
    }

    public static function fromDomainDetailed(BlogPost $post, StoragePort $storage): self
    {
        return new self(
            id: $post->id(),
            title: $post->title(),
            slug: $post->slug(),
            excerpt: $post->excerpt(),
            tag: $post->tag(),
            publishedAt: $post->publishedAt()?->format('Y-m-d'),
            coverImage: self::buildUrl($post->coverImage(), $storage),
            content: $post->content(),
        );
    }

    public static function fromDomainAdmin(BlogPost $post, StoragePort $storage): self
    {
        return new self(
            id: $post->id(),
            title: $post->title(),
            slug: $post->slug(),
            excerpt: $post->excerpt(),
            tag: $post->tag(),
            publishedAt: $post->publishedAt()?->format('Y-m-d H:i:s'),
            coverImage: self::buildUrl($post->coverImage(), $storage),
            createdAt: $post->createdAt()->format('Y-m-d H:i:s'),
            content: $post->content(),
            isPublished: $post->isPublished(),
        );
    }

    /**
     * @param BlogPost[] $posts
     * @return self[]
     */
    public static function fromDomainList(array $posts, StoragePort $storage): array
    {
        return array_map(fn (BlogPost $p) => self::fromDomain($p, $storage), $posts);
    }

    /**
     * @param BlogPost[] $posts
     * @return self[]
     */
    public static function fromDomainListAdmin(array $posts, StoragePort $storage): array
    {
        return array_map(fn (BlogPost $p) => self::fromDomainAdmin($p, $storage), $posts);
    }

    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'tag' => $this->tag,
            'publishedAt' => $this->publishedAt,
            'coverImage' => $this->coverImage,
        ];

        if ($this->createdAt !== null) {
            $data['createdAt'] = $this->createdAt;
        }
        if ($this->content !== null) {
            $data['content'] = $this->content;
        }
        if ($this->isPublished !== null) {
            $data['isPublished'] = $this->isPublished;
        }

        return $data;
    }

    /**
     * @param self[] $dtos
     * @return array<int, array<string, mixed>>
     */
    public static function listToArray(array $dtos): array
    {
        return array_map(fn (self $dto) => $dto->toArray(), $dtos);
    }
}
