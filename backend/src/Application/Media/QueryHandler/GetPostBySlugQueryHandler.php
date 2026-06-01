<?php

declare(strict_types=1);

namespace App\Application\Media\QueryHandler;

use App\Application\Media\Query\GetPostBySlugQuery;
use App\Application\Media\Response\BlogPostResponseDto;
use App\Domain\Media\Port\StoragePort;
use App\Domain\Media\Repository\BlogPostRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class GetPostBySlugQueryHandler
{
    public function __construct(
        private BlogPostRepositoryInterface $repository,
        private StoragePort $storage,
    ) {
    }

    public function __invoke(GetPostBySlugQuery $query): ?BlogPostResponseDto
    {
        $post = $this->repository->findBySlug($query->slug);

        return $post !== null ? BlogPostResponseDto::fromDomainDetailed($post, $this->storage) : null;
    }
}
