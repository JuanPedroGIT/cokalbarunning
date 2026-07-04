<?php

declare(strict_types=1);

namespace App\Application\Runner\Search;

final readonly class SearchRunnersQuery
{
    public function __construct(
        public ?string $editionId = null,
        public ?string $name = null,
        public ?string $bib = null,
    ) {
    }
}
