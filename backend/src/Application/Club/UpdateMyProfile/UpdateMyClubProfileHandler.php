<?php

declare(strict_types=1);

namespace App\Application\Club\UpdateMyProfile;

use App\Domain\Club\Repository\ClubMemberRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class UpdateMyClubProfileHandler
{
    public function __construct(
        private ClubMemberRepositoryInterface $clubMemberRepository,
    ) {
    }

    public function __invoke(UpdateMyClubProfileCommand $command): void
    {
        $member = $this->clubMemberRepository->findByUserId($command->userId);
        if (!$member) {
            throw new \RuntimeException('No club member profile');
        }

        $member->update(
            name: $command->name ?? $member->name(),
            description: $member->description(),
            bio: $command->bio !== null ? $command->bio : $member->bio(),
            photoPath: $member->photoPath(),
            isActive: $member->isActive(),
            sortOrder: $member->sortOrder(),
            userId: $member->userId(),
        );

        $this->clubMemberRepository->save($member);
    }
}
