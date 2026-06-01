<?php

declare(strict_types=1);

namespace App\Application\Media\QueryHandler;

use App\Application\Media\Query\GetFeaturedPhotosQuery;
use App\Application\Media\Response\PhotoResponseDto;
use App\Domain\Media\Port\StoragePort;
use App\Domain\Media\Repository\PhotoRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class GetFeaturedPhotosQueryHandler
{
    public function __construct(
        private PhotoRepositoryInterface $repository,
        private StoragePort $storage,
    ) {
    }

    /**
     * @return PhotoResponseDto[]
     */
    public function __invoke(GetFeaturedPhotosQuery $query): array
    {
        $photos = $this->repository->findFeatured();

        return PhotoResponseDto::fromDomainList(
            $photos,
            fn (string $path) => $this->storage->url($path)
        );
    }
}
