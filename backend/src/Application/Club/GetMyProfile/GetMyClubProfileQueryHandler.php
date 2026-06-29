<?php

declare(strict_types=1);

namespace App\Application\Club\GetMyProfile;

use App\Application\Club\Response\ClubMemberResponseDto;
use App\Domain\Club\Repository\ClubMemberRepositoryInterface;
use App\Domain\Media\Port\StoragePort;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class GetMyClubProfileQueryHandler
{
    public function __construct(
        private ClubMemberRepositoryInterface $clubMemberRepository,
        private StoragePort $storage,
    ) {
    }

    public function __invoke(GetMyClubProfileQuery $query): ?ClubMemberResponseDto
    {
        $member = $this->clubMemberRepository->findByUserId($query->userId);
        if (!$member) {
            return null;
        }

        return ClubMemberResponseDto::fromDomain($member, $this->storage);
    }
}
