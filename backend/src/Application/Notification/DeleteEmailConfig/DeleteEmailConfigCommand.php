<?php

declare(strict_types=1);

namespace App\Application\Notification\DeleteEmailConfig;

final readonly class DeleteEmailConfigCommand
{
    public function __construct(
        public string $id,
    ) {
    }
}
