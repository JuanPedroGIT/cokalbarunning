<?php

declare(strict_types=1);

namespace App\Application\Club\QueryHandler;

use App\Application\Club\Query\GetAllSponsorsQuery;
use App\Application\Club\Response\SponsorResponseDto;
use App\Domain\Club\Repository\SponsorRepositoryInterface;
use App\Domain\Media\Port\StoragePort;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class GetAllSponsorsQueryHandler
{
    public function __construct(
        private SponsorRepositoryInterface $repository,
        private StoragePort $storage,
    ) {
    }

    /**
     * @return SponsorResponseDto[]
     */
    public function __invoke(GetAllSponsorsQuery $query): array
    {
        $sponsors = $this->repository->findAll();

        return SponsorResponseDto::fromDomainList($sponsors, $this->storage);
    }
}
