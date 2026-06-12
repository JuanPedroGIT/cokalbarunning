<?php

declare(strict_types=1);

namespace App\Application\Media\QueryHandler;

use App\Application\Media\Query\GetActiveBannerQuery;
use App\Application\Media\Response\BlogPostResponseDto;
use App\Domain\Media\Entity\BlogPost;
use App\Domain\Media\Port\StoragePort;
use App\Domain\Media\Repository\BlogPostRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class GetActiveBannerQueryHandler
{
    public function __construct(
        private BlogPostRepositoryInterface $repository,
        private StoragePort $storage,
    ) {
    }

    public function __invoke(GetActiveBannerQuery $query): ?BlogPostResponseDto
    {
        $post = $this->repository->findLatestPublishedByType(BlogPost::TYPE_BANNER);

        return $post !== null ? BlogPostResponseDto::fromDomain($post, $this->storage) : null;
    }
}
