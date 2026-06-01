<?php

declare(strict_types=1);

namespace App\Application\Media\Query;

final readonly class GetAllPhotosQuery
{
    public function __construct(
        public ?string $editionId = null,
    ) {
    }
}
