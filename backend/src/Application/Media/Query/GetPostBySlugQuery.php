<?php

declare(strict_types=1);

namespace App\Application\Media\Query;

final readonly class GetPostBySlugQuery
{
    public function __construct(public string $slug)
    {
    }
}
