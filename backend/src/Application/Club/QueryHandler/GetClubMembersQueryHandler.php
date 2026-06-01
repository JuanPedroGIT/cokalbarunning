<?php

declare(strict_types=1);

namespace App\Application\Club\QueryHandler;

use App\Application\Club\Query\GetClubMembersQuery;
use App\Application\Club\Response\ClubMemberResponseDto;
use App\Domain\Club\Repository\ClubMemberRepositoryInterface;
use App\Domain\Media\Port\StoragePort;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class GetClubMembersQueryHandler
{
    public function __construct(
        private ClubMemberRepositoryInterface $repository,
        private StoragePort $storage,
    ) {
    }

    /**
     * @return ClubMemberResponseDto[]
     */
    public function __invoke(GetClubMembersQuery $query): array
    {
        $members = $query->onlyActive
            ? $this->repository->findAllActive()
            : $this->repository->findAll();

        return ClubMemberResponseDto::fromDomainList($members, $this->storage);
    }
}
