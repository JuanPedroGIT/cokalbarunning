<?php

declare(strict_types=1);

namespace App\Application\User\Create;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsMessageHandler]
final class CreateUserHandler
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $hasher,
    ) {
    }

    public function __invoke(CreateUserCommand $command): string
    {
        $existing = $this->em->getRepository(User::class)->findOneBy(['email' => $command->email]);
        if ($existing) {
            throw new \RuntimeException('Email already exists');
        }

        $user = new User();
        $user->setId(Uuid::uuid4()->toString());
        $user->setEmail($command->email);
        $user->setFirstName($command->firstName);
        $user->setLastName($command->lastName);
        $user->setRoles($command->roles);
        $user->setPassword($this->hasher->hashPassword($user, $command->password));

        $this->em->persist($user);
        $this->em->flush();

        return $user->getId();
    }
}
