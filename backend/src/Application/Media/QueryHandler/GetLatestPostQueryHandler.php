<?php

declare(strict_types=1);

namespace App\Application\Media\QueryHandler;

use App\Application\Media\Query\GetLatestPostQuery;
use App\Application\Media\Response\BlogPostResponseDto;
use App\Domain\Media\Port\StoragePort;
use App\Domain\Media\Repository\BlogPostRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class GetLatestPostQueryHandler
{
    public function __construct(
        private BlogPostRepositoryInterface $repository,
        private StoragePort $storage,
    ) {
    }

    public function __invoke(GetLatestPostQuery $query): ?BlogPostResponseDto
    {
        $post = $this->repository->findLatestPublished();

        return $post !== null ? BlogPostResponseDto::fromDomain($post, $this->storage) : null;
    }
}
