<?php

declare(strict_types=1);

namespace App\Application\Runner\Search;

final readonly class SearchRunnersQuery
{
    public function __construct(
        public string $editionId,
        public string $name,
    ) {
    }
}
