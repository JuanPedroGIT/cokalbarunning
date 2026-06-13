<?php

declare(strict_types=1);

namespace App\Application\Race\BibEmail;

final readonly class SendBibEmailMessage
{
    public function __construct(
        public string $logId,
        public string $recipientEmail,
        public string $recipientName,
        public string $bibNumber,
        public ?string $raceEditionId = null,
        public ?string $sentBy = null,
    ) {
    }
}
