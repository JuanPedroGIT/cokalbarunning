<?php

declare(strict_types=1);

namespace App\Application\User\Update;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsMessageHandler]
final class UpdateUserHandler
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $hasher,
    ) {
    }

    public function __invoke(UpdateUserCommand $command): void
    {
        $user = $this->em->getRepository(User::class)->find($command->id);
        if (!$user) {
            throw new \RuntimeException('User not found');
        }

        if ($command->email !== null) {
            $user->setEmail($command->email);
        }
        if ($command->firstName !== null) {
            $user->setFirstName($command->firstName);
        }
        if ($command->lastName !== null) {
            $user->setLastName($command->lastName);
        }
        if ($command->roles !== null) {
            $user->setRoles($command->roles);
        }
        if ($command->password !== null && $command->password !== '') {
            $user->setPassword($this->hasher->hashPassword($user, $command->password));
        }

        $this->em->flush();
    }
}
