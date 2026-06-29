<?php

declare(strict_types=1);

namespace App\Application\User\ChangePassword;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsMessageHandler]
final class ChangePasswordHandler
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function __invoke(ChangePasswordCommand $command): void
    {
        $user = $this->em->getRepository(User::class)->find($command->userId);
        if (!$user) {
            throw new \RuntimeException('User not found');
        }

        if (!$this->passwordHasher->isPasswordValid($user, $command->currentPassword)) {
            throw new \RuntimeException('Current password is incorrect');
        }

        if (\strlen($command->newPassword) < 6) {
            throw new \RuntimeException('New password must be at least 6 characters');
        }

        $user->setPassword($this->passwordHasher->hashPassword($user, $command->newPassword));
        $this->em->flush();
    }
}
