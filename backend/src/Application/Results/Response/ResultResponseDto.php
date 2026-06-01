<?php

declare(strict_types=1);

namespace App\Application\Results\Response;

use App\Domain\Results\Entity\Result;

final readonly class ResultResponseDto
{
    public function __construct(
        public string $id,
        public ?int $position,
        public string $bibNumber,
        public RunnerResponseDto $runner,
        public string $category,
        public string $finishTime,
    ) {
    }

    public static function fromDomain(Result $result): self
    {
        return new self(
            id: $result->id(),
            position: $result->position()?->value(),
            bibNumber: $result->bibNumber()->value(),
            runner: RunnerResponseDto::fromDomain($result->runner()),
            category: $result->category()->name(),
            finishTime: $result->finishTime()->format(),
        );
    }

    /**
     * @param Result[] $results
     * @return self[]
     */
    public static function fromDomainList(array $results): array
    {
        return array_map(fn (Result $r) => self::fromDomain($r), $results);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'position' => $this->position,
            'bibNumber' => $this->bibNumber,
            'runner' => $this->runner->toArray(),
            'category' => $this->category,
            'finishTime' => $this->finishTime,
        ];
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
