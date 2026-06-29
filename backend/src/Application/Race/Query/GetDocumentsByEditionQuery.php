<?php

declare(strict_types=1);

namespace App\Application\Race\Query;

final readonly class GetDocumentsByEditionQuery
{
    public function __construct(
        public ?string $editionId = null,
        public ?int $year = null,
    ) {
    }
}
