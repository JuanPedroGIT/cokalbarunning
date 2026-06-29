<?php

declare(strict_types=1);

namespace App\Application\Notification\CreateEmailConfig;

final readonly class CreateEmailConfigCommand
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        public string $editionId,
        public string $type,
        public array $data,
    ) {
    }
}
