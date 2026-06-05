<?php

declare(strict_types=1);

namespace App\Application\Media\Create;

final class CreateBlogPostCommand
{
    public function __construct(
        public string $title,
        public string $excerpt,
        public string $content,
        public string $tag,
        public ?string $publishedAt = null,
        public ?string $coverImage = null,
        public ?int $priority = null,
    ) {
    }
}
