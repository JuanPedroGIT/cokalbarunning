<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Api;

use App\Application\Club\GetMyProfile\GetMyClubProfileQuery;
use App\Application\Club\Response\ClubMemberResponseDto;
use App\Application\Club\UpdateMyProfile\UpdateMyClubProfileCommand;
use App\Application\User\ChangePassword\ChangePasswordCommand;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/v1/me')]
class MeController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $commandBus,
        private MessageBusInterface $queryBus,
    ) {
    }

    #[Route('/club-profile', methods: ['GET'])]
    public function clubProfile(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $envelope = $this->queryBus->dispatch(new GetMyClubProfileQuery(userId: $user->getId()));
        /** @var ClubMemberResponseDto|null $dto */
        $dto = $envelope->last(HandledStamp::class)?->getResult();

        if (!$dto) {
            return $this->json(['error' => 'No club member profile'], 404);
        }

        return $this->json(['data' => $dto->toArray()]);
    }

    #[Route('/club-profile', methods: ['PUT'])]
    public function updateClubProfile(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $data = json_decode($request->getContent(), true);
        if (!\is_array($data)) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }

        try {
            $this->commandBus->dispatch(new UpdateMyClubProfileCommand(
                userId: $user->getId(),
                name: array_key_exists('name', $data) ? ($data['name'] ?? null) : null,
                bio: array_key_exists('bio', $data) ? $data['bio'] : null,
            ));

            return $this->json(['data' => ['updated' => true]]);
        } catch (HandlerFailedException $e) {
            $msg = $e->getPrevious()?->getMessage() ?? $e->getMessage();
            return $this->json(['error' => $msg], 404);
        }
    }

    #[Route('/password', methods: ['PUT'])]
    public function changePassword(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $data = json_decode($request->getContent(), true);
        if (!\is_array($data)) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }

        $currentPassword = $data['currentPassword'] ?? '';
        $newPassword = $data['newPassword'] ?? '';

        try {
            $this->commandBus->dispatch(new ChangePasswordCommand(
                userId: $user->getId(),
                currentPassword: $currentPassword,
                newPassword: $newPassword,
            ));

            return $this->json(['data' => ['updated' => true]]);
        } catch (HandlerFailedException $e) {
            $msg = $e->getPrevious()?->getMessage() ?? $e->getMessage();
            $statusCode = match ($msg) {
                'Current password is incorrect' => 400,
                'New password must be at least 6 characters' => 400,
                default => 404,
            };
            return $this->json(['error' => $msg], $statusCode);
        }
    }
}
