<?php

declare(strict_types=1);

namespace App\Application\User\Query;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class GetAllUsersQueryHandler
{
    public function __construct(
        private EntityManagerInterface $em,
    ) {
    }

    public function __invoke(GetAllUsersQuery $query): array
    {
        $users = $this->em->getRepository(User::class)->findAll();

        return array_map(fn (User $u): array => [
            'id' => $u->getId(),
            'email' => $u->getEmail(),
            'firstName' => $u->getFirstName(),
            'lastName' => $u->getLastName(),
            'roles' => $u->getRoles(),
        ], $users);
    }
}
