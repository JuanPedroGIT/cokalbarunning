<?php

declare(strict_types=1);

namespace App\Application\Media\QueryHandler;

use App\Application\Media\Query\GetAllPhotosQuery;
use App\Application\Media\Response\PhotoResponseDto;
use App\Domain\Media\Port\StoragePort;
use App\Domain\Media\Repository\PhotoRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class GetAllPhotosQueryHandler
{
    public function __construct(
        private PhotoRepositoryInterface $repository,
        private StoragePort $storage,
    ) {
    }

    /**
     * @return PhotoResponseDto[]
     */
    public function __invoke(GetAllPhotosQuery $query): array
    {
        $photos = $query->editionId !== null
            ? $this->repository->findByEditionId($query->editionId)
            : $this->repository->findAll();

        return PhotoResponseDto::fromDomainList(
            $photos,
            fn (string $path) => $this->storage->url($path)
        );
    }
}
