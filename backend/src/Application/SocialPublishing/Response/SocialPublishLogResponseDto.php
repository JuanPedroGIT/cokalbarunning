<?php

declare(strict_types=1);

namespace App\Application\SocialPublishing\Response;

use App\Domain\SocialPublishing\Entity\SocialPublishLog;

final readonly class SocialPublishLogResponseDto
{
    public function __construct(
        public string $id,
        public string $postId,
        public string $network,
        public string $status,
        public ?string $publishedAt,
        public ?string $externalUrl,
        public ?string $publishedBy,
        public ?string $createdAt = null,
    ) {
    }

    public static function fromDomain(SocialPublishLog $log): self
    {
        return new self(
            id: $log->id(),
            postId: $log->postId(),
            network: $log->network(),
            status: $log->status(),
            publishedAt: $log->publishedAt()?->format('Y-m-d H:i:s'),
            externalUrl: $log->externalUrl(),
            publishedBy: $log->publishedBy(),
            createdAt: $log->createdAt()->format('Y-m-d H:i:s'),
        );
    }

    /**
     * @param SocialPublishLog[] $logs
     * @return self[]
     */
    public static function fromDomainList(array $logs): array
    {
        return array_map(fn (SocialPublishLog $log) => self::fromDomain($log), $logs);
    }

    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'postId' => $this->postId,
            'network' => $this->network,
            'status' => $this->status,
        ];

        if ($this->publishedAt !== null) {
            $data['publishedAt'] = $this->publishedAt;
        }
        if ($this->externalUrl !== null) {
            $data['externalUrl'] = $this->externalUrl;
        }
        if ($this->publishedBy !== null) {
            $data['publishedBy'] = $this->publishedBy;
        }
        if ($this->createdAt !== null) {
            $data['createdAt'] = $this->createdAt;
        }

        return $data;
    }

    /**
     * @param self[] $dtos
     * @return array<int, array<string, mixed>>
     */
    public static function listToArray(array $dtos): array
    {
        return array_map(fn (self $dto) => $dto->toArray(), $dtos);
    }
}
