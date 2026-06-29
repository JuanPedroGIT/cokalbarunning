<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Api\Admin;

use App\Application\User\Create\CreateUserCommand;
use App\Application\User\Delete\DeleteUserCommand;
use App\Application\User\Query\GetAllUsersQuery;
use App\Application\User\Update\UpdateUserCommand;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/admin')]
class AdminUserController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $commandBus,
        private MessageBusInterface $queryBus,
    ) {
    }

    #[Route('/users', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $envelope = $this->queryBus->dispatch(new GetAllUsersQuery());
        $data = $envelope->last(HandledStamp::class)?->getResult() ?? [];

        return $this->json(['data' => $data]);
    }

    #[Route('/users', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!\is_array($data) || empty($data['email']) || empty($data['password'])) {
            return $this->json(['error' => 'Email and password required'], 400);
        }

        try {
            $envelope = $this->commandBus->dispatch(new CreateUserCommand(
                email: $data['email'],
                password: $data['password'],
                firstName: $data['firstName'] ?? '',
                lastName: $data['lastName'] ?? '',
                roles: $data['roles'] ?? ['ROLE_EDITOR'],
            ));
            $id = $envelope->last(HandledStamp::class)?->getResult();

            return $this->json(['data' => ['id' => $id]], 201);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], 409);
        }
    }

    #[Route('/users/{id}', methods: ['PUT'])]
    public function update(string $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!\is_array($data)) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }

        try {
            $this->commandBus->dispatch(new UpdateUserCommand(
                id: $id,
                email: $data['email'] ?? null,
                password: $data['password'] ?? null,
                firstName: $data['firstName'] ?? null,
                lastName: $data['lastName'] ?? null,
                roles: $data['roles'] ?? null,
            ));

            return $this->json(['data' => ['updated' => true]]);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], 404);
        }
    }

    #[Route('/users/{id}', methods: ['DELETE'])]
    public function delete(string $id): JsonResponse
    {
        try {
            $this->commandBus->dispatch(new DeleteUserCommand(id: $id));

            return $this->json(null, 204);
        } catch (\RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], 404);
        }
    }
}
