<?php

declare(strict_types=1);

namespace App\Application\Notification\Query;

final readonly class GetEmailSendLogsQuery
{
    public function __construct(
        public ?string $raceEditionId = null,
    ) {
    }
}
