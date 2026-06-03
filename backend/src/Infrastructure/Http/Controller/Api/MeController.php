<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Api;

use App\Application\Club\Response\ClubMemberResponseDto;
use App\Domain\Club\Repository\ClubMemberRepositoryInterface;
use App\Domain\Media\Port\StoragePort;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/v1/me')]
class MeController extends AbstractController
{
    public function __construct(
        private ClubMemberRepositoryInterface $clubMemberRepository,
        private StoragePort $storage,
        private UserPasswordHasherInterface $passwordHasher,
        private EntityManagerInterface $em,
    ) {
    }

    #[Route('/club-profile', methods: ['GET'])]
    public function clubProfile(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $member = $this->clubMemberRepository->findByUserId($user->getId());
        if (!$member) {
            return $this->json(['error' => 'No club member profile'], 404);
        }

        return $this->json([
            'data' => ClubMemberResponseDto::fromDomain($member, $this->storage)->toArray(),
        ]);
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

        $member = $this->clubMemberRepository->findByUserId($user->getId());
        if (!$member) {
            return $this->json(['error' => 'No club member profile'], 404);
        }

        $member->update(
            name: array_key_exists('name', $data) ? ($data['name'] ?? $member->name()) : $member->name(),
            description: $member->description(),
            bio: array_key_exists('bio', $data) ? $data['bio'] : $member->bio(),
            photoPath: $member->photoPath(),
            isActive: $member->isActive(),
            sortOrder: $member->sortOrder(),
            userId: $member->userId(),
        );

        $this->clubMemberRepository->save($member);

        return $this->json(['data' => ['updated' => true]]);
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

        if (!$this->passwordHasher->isPasswordValid($user, $currentPassword)) {
            return $this->json(['error' => 'Current password is incorrect'], 400);
        }

        if (\strlen($newPassword) < 6) {
            return $this->json(['error' => 'New password must be at least 6 characters'], 400);
        }

        $user->setPassword($this->passwordHasher->hashPassword($user, $newPassword));
        $this->em->flush();

        return $this->json(['data' => ['updated' => true]]);
    }
}
