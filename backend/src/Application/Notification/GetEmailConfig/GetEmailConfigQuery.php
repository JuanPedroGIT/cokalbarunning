<?php

declare(strict_types=1);

namespace App\Application\Notification\GetEmailConfig;

final readonly class GetEmailConfigQuery
{
    public function __construct(
        public string $editionId,
        public string $type,
    ) {
    }
}
