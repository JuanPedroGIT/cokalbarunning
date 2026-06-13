<?php

declare(strict_types=1);

namespace App\Application\Notification\Response;

use App\Domain\Notification\Entity\EmailSendLog;

final readonly class EmailSendLogResponseDto
{
    public function __construct(
        public string $id,
        public string $recipientEmail,
        public string $recipientName,
        public string $bibNumber,
        public ?string $raceEditionId,
        public string $status,
        public ?string $errorMessage,
        public ?string $sentAt,
        public ?string $sentBy,
        public string $createdAt,
    ) {
    }

    public static function fromDomain(EmailSendLog $log): self
    {
        return new self(
            id: $log->id(),
            recipientEmail: $log->recipientEmail(),
            recipientName: $log->recipientName(),
            bibNumber: $log->bibNumber(),
            raceEditionId: $log->raceEditionId(),
            status: $log->status()->value(),
            errorMessage: $log->errorMessage(),
            sentAt: $log->sentAt()?->format('Y-m-d H:i:s'),
            sentBy: $log->sentBy(),
            createdAt: $log->createdAt()?->format('Y-m-d H:i:s') ?? '',
        );
    }

    /**
     * @param EmailSendLog[] $logs
     * @return array<int, array<string, mixed>>
     */
    public static function listToArray(array $logs): array
    {
        return array_map(fn (EmailSendLog $log) => self::fromDomain($log)->toArray(), $logs);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'recipientEmail' => $this->recipientEmail,
            'recipientName' => $this->recipientName,
            'bibNumber' => $this->bibNumber,
            'raceEditionId' => $this->raceEditionId,
            'status' => $this->status,
            'errorMessage' => $this->errorMessage,
            'sentAt' => $this->sentAt,
            'sentBy' => $this->sentBy,
            'createdAt' => $this->createdAt,
        ];
    }
}
