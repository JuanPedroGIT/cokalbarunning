<?php

declare(strict_types=1);

namespace App\Application\Notification\GetSentCounts;

final readonly class GetEmailSentCountsQuery
{
    public function __construct(
        public string $type,
        public ?string $raceEditionId = null,
    ) {
    }
}
