<?php

declare(strict_types=1);

namespace App\Application\User\Delete;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class DeleteUserHandler
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {
    }

    public function __invoke(DeleteUserCommand $command): void
    {
        $user = $this->em->getRepository(User::class)->find($command->id);
        if (!$user) {
            throw new \RuntimeException('User not found');
        }

        $this->em->remove($user);
        $this->em->flush();
    }
}
