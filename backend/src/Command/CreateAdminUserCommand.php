<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:user:create',
    description: 'Crea o actualiza un usuario administrador',
)]
final class CreateAdminUserCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Email del usuario')
            ->addOption('password', 'p', InputOption::VALUE_REQUIRED, 'Contraseña (si no se indica, se genera una aleatoria)')
            ->addOption('first-name', 'f', InputOption::VALUE_REQUIRED, 'Nombre', 'Admin')
            ->addOption('last-name', 'l', InputOption::VALUE_REQUIRED, 'Apellidos', 'User')
            ->addOption('role', 'r', InputOption::VALUE_REQUIRED, 'Rol principal (ROLE_ADMIN o ROLE_EDITOR)', 'ROLE_ADMIN')
            ->addOption('update', 'u', InputOption::VALUE_NONE, 'Actualizar usuario existente');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = $input->getArgument('email');
        $password = $input->getOption('password');
        $firstName = $input->getOption('first-name');
        $lastName = $input->getOption('last-name');
        $role = $input->getOption('role');
        $update = $input->getOption('update');

        $userRepository = $this->entityManager->getRepository(User::class);
        $existingUser = $userRepository->findOneBy(['email' => $email]);

        if ($existingUser && !$update) {
            $io->error(sprintf('El usuario "%s" ya existe. Usa --update para modificarlo.', $email));

            return Command::FAILURE;
        }

        if ($existingUser && $update) {
            $user = $existingUser;
            $io->note(sprintf('Actualizando usuario existente: %s', $email));
        } else {
            $user = new User();
            $user->setId(Uuid::uuid4()->toString());
            $user->setEmail($email);
            $user->setFirstName($firstName);
            $user->setLastName($lastName);
            $io->note(sprintf('Creando nuevo usuario: %s', $email));
        }

        $user->setRoles([$role]);

        if (!$password) {
            $password = bin2hex(random_bytes(4));
            $io->warning(sprintf('No se indicó contraseña. Se ha generado una aleatoria: %s', $password));
        }

        $user->setPassword($this->passwordHasher->hashPassword($user, $password));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $io->success([
            sprintf('Usuario %s correctamente.', $existingUser && $update ? 'actualizado' : 'creado'),
            sprintf('Email: %s', $email),
            sprintf('Rol: %s', $role),
        ]);

        return Command::SUCCESS;
    }
}
