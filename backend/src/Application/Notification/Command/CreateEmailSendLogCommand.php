<?php

declare(strict_types=1);

namespace App\Application\Notification\Command;

final readonly class CreateEmailSendLogCommand
{
    public function __construct(
        public string $id,
        public string $recipientEmail,
        public string $recipientName,
        public string $bibNumber,
        public ?string $raceEditionId = null,
    ) {
    }
}
