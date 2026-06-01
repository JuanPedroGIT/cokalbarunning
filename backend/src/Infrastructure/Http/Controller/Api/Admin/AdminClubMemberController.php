<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Api\Admin;

use App\Application\Club\Create\CreateClubMemberCommand;
use App\Application\Club\Delete\DeleteClubMemberCommand;
use App\Application\Club\Query\GetClubMembersQuery;
use App\Application\Club\Response\ClubMemberResponseDto;
use App\Application\Club\Update\UpdateClubMemberCommand;
use App\Application\Club\UploadPhoto\UploadClubMemberPhotoCommand;
use App\Entity\ClubMember as OrmClubMember;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/admin')]
class AdminClubMemberController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $commandBus,
        private MessageBusInterface $queryBus,
        private EntityManagerInterface $em,
    ) {
    }

    #[Route('/club-members', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $envelope = $this->queryBus->dispatch(new GetClubMembersQuery(onlyActive: false));
        /** @var ClubMemberResponseDto[] $dtos */
        $dtos = $envelope->last(HandledStamp::class)?->getResult() ?? [];

        return $this->json(['data' => ClubMemberResponseDto::listToArray($dtos)]);
    }

    #[Route('/club-members', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!\is_array($data)) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }

        $envelope = $this->commandBus->dispatch(new CreateClubMemberCommand(
            name: $data['name'],
            description: $data['description'] ?? null,
            isActive: $data['isActive'] ?? true,
            sortOrder: $data['sortOrder'] ?? 0,
        ));
        $id = $envelope->last(HandledStamp::class)?->getResult();

        if ($id) {
            $orm = $this->em->getRepository(OrmClubMember::class)->find($id);
            if ($orm) { $orm->setCreatedBy($this->getUser()->getEmail()); $this->em->flush(); }
        }

        return $this->json(['data' => ['id' => $id]], 201);
    }

    #[Route('/club-members/{id}', methods: ['PUT'])]
    public function update(string $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!\is_array($data)) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }

        $this->commandBus->dispatch(new UpdateClubMemberCommand(
            id: $id,
            name: $data['name'] ?? null,
            description: array_key_exists('description', $data) ? $data['description'] : null,
            isActive: $data['isActive'] ?? null,
            sortOrder: $data['sortOrder'] ?? null,
        ));

        $orm = $this->em->getRepository(OrmClubMember::class)->find($id);
        if ($orm) { $orm->setUpdatedBy($this->getUser()->getEmail()); $this->em->flush(); }

        return $this->json(['data' => ['updated' => true]]);
    }

    #[Route('/club-members/{id}', methods: ['DELETE'])]
    public function delete(string $id): JsonResponse
    {
        $this->commandBus->dispatch(new DeleteClubMemberCommand($id));

        return $this->json(null, 204);
    }

    #[Route('/club-members/{id}/photo', methods: ['POST'])]
    public function uploadPhoto(string $id, Request $request): JsonResponse
    {
        $file = $request->files->get('file');
        if (!$file || !$file->isValid()) {
            return $this->json(['error' => 'No file uploaded'], 400);
        }

        $envelope = $this->commandBus->dispatch(new UploadClubMemberPhotoCommand(
            memberId: $id,
            originalName: $file->getClientOriginalName(),
            mimeType: $file->getMimeType() ?? 'image/jpeg',
            tmpPath: $file->getPathname(),
        ));

        $url = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json(['data' => ['photoUrl' => $url]]);
    }
}
