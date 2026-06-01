<?php

declare(strict_types=1);

namespace App\Application\Media\Delete;

final class DeleteBlogPostCommand
{
    public function __construct(
        public string $id,
    ) {
    }
}
