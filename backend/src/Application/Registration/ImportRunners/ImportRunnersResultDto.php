<?php

declare(strict_types=1);

namespace App\Application\Registration\ImportRunners;

final readonly class ImportRunnersResultDto
{
    public function __construct(
        public int $created,
        public int $updated,
        public int $skipped,
        public int $total,
    ) {
    }

    /** @return array<string, int> */
    public function toArray(): array
    {
        return [
            'created' => $this->created,
            'updated' => $this->updated,
            'skipped' => $this->skipped,
            'total' => $this->total,
        ];
    }
}
