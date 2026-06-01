<?php

declare(strict_types=1);

namespace App\Application\Media\QueryHandler;

use App\Application\Media\Query\GetAllPostsQuery;
use App\Application\Media\Response\BlogPostResponseDto;
use App\Domain\Media\Port\StoragePort;
use App\Domain\Media\Repository\BlogPostRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class GetAllPostsQueryHandler
{
    public function __construct(
        private BlogPostRepositoryInterface $repository,
        private StoragePort $storage,
    ) {
    }

    /**
     * @return BlogPostResponseDto[]
     */
    public function __invoke(GetAllPostsQuery $query): array
    {
        $posts = $this->repository->findAll();

        return BlogPostResponseDto::fromDomainListAdmin($posts, $this->storage);
    }
}
