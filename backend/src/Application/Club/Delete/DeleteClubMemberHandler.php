<?php

declare(strict_types=1);

namespace App\Application\Club\Delete;

use App\Domain\Club\Repository\ClubMemberRepositoryInterface;
use App\Domain\Media\Port\StoragePort;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class DeleteClubMemberHandler
{
    public function __construct(
        private ClubMemberRepositoryInterface $repository,
        private StoragePort $storage,
    ) {
    }

    public function __invoke(DeleteClubMemberCommand $command): void
    {
        $member = $this->repository->findById($command->id);
        if ($member !== null) {
            if ($member->photoPath()) {
                $this->storage->delete($member->photoPath());
            }
            $this->repository->remove($member);
        }
    }
}
