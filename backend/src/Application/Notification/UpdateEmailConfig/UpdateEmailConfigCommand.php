<?php

declare(strict_types=1);

namespace App\Application\Notification\UpdateEmailConfig;

final readonly class UpdateEmailConfigCommand
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        public string $id,
        public array $data,
    ) {
    }
}
