<?php

declare(strict_types=1);

namespace App\Application\Notification\Command;

final readonly class CreateEmailSendLogCommand
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $id,
        public string $type,
        public string $recipientEmail,
        public string $recipientName,
        public ?string $reference = null,
        public ?string $raceEditionId = null,
        public ?string $sentBy = null,
        public array $metadata = [],
    ) {
    }
}
