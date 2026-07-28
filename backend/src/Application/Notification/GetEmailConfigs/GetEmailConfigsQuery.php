<?php

declare(strict_types=1);

namespace App\Application\Notification\GetEmailConfigs;

final readonly class GetEmailConfigsQuery
{
    public function __construct(
        public ?string $editionId = null,
    ) {
    }
}
