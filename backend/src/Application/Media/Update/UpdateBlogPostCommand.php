<?php

declare(strict_types=1);

namespace App\Application\Media\Update;

final class UpdateBlogPostCommand
{
    public function __construct(
        public string $id,
        public ?string $title = null,
        public ?string $excerpt = null,
        public ?string $content = null,
        public ?string $tag = null,
        public ?string $publishedAt = null,
        public ?string $coverImage = null,
    ) {
    }
}
