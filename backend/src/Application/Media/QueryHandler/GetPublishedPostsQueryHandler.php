<?php

declare(strict_types=1);

namespace App\Application\Media\QueryHandler;

use App\Application\Media\Query\GetPublishedPostsQuery;
use App\Application\Media\Response\BlogPostResponseDto;
use App\Domain\Media\Port\StoragePort;
use App\Domain\Media\Repository\BlogPostRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class GetPublishedPostsQueryHandler
{
    public function __construct(
        private BlogPostRepositoryInterface $repository,
        private StoragePort $storage,
    ) {
    }

    /**
     * @return BlogPostResponseDto[]
     */
    public function __invoke(GetPublishedPostsQuery $query): array
    {
        $posts = $this->repository->findPublished();

        return BlogPostResponseDto::fromDomainList($posts, $this->storage);
    }
}
