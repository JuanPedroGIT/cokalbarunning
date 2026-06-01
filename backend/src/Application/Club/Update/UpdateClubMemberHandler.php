<?php

declare(strict_types=1);

namespace App\Application\Club\Update;

use App\Domain\Club\Repository\ClubMemberRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class UpdateClubMemberHandler
{
    public function __construct(
        private ClubMemberRepositoryInterface $repository,
    ) {
    }

    public function __invoke(UpdateClubMemberCommand $command): void
    {
        $member = $this->repository->findById($command->id);
        if (!$member) {
            return;
        }

        $member->update(
            name: $command->name ?? $member->name(),
            description: $command->description ?? $member->description(),
            photoPath: $command->photoPath ?? $member->photoPath(),
            isActive: $command->isActive ?? $member->isActive(),
            sortOrder: $command->sortOrder ?? $member->sortOrder(),
        );

        $this->repository->save($member);
    }
}
