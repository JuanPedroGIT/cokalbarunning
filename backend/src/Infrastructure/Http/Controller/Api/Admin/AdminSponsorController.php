<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Api\Admin;

use App\Application\Club\Create\CreateSponsorCommand;
use App\Application\Club\Delete\DeleteSponsorCommand;
use App\Application\Club\Query\GetAllSponsorsQuery;
use App\Application\Club\Response\SponsorResponseDto;
use App\Application\Club\Update\UpdateSponsorCommand;
use App\Application\Club\UploadLogo\UploadSponsorLogoCommand;
use App\Entity\Sponsor as OrmSponsor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/admin')]
class AdminSponsorController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $commandBus,
        private MessageBusInterface $queryBus,
        private EntityManagerInterface $em,
    ) {
    }

    #[Route('/sponsors', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $envelope = $this->queryBus->dispatch(new GetAllSponsorsQuery());
        /** @var SponsorResponseDto[] $dtos */
        $dtos = $envelope->last(HandledStamp::class)?->getResult() ?? [];

        return $this->json([
            'data' => SponsorResponseDto::listToArray($dtos),
        ]);
    }

    #[Route('/sponsors', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!\is_array($data)) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }

        $command = new CreateSponsorCommand(
            name: $data['name'],
            logoUrl: $data['logoUrl'] ?? null,
            website: $data['website'] ?? null,
            tier: $data['tier'] ?? 'bronze',
            isActive: $data['isActive'] ?? true,
            sortOrder: $data['sortOrder'] ?? 0,
            message: $data['message'] ?? null,
        );

        /** @var \Symfony\Component\Messenger\Stamp\HandledStamp|null $stamp */
        $envelope = $this->commandBus->dispatch($command);
        $id = $envelope->last(\Symfony\Component\Messenger\Stamp\HandledStamp::class)?->getResult();

        if ($id) {
            $orm = $this->em->getRepository(OrmSponsor::class)->find($id);
            if ($orm) { $orm->setCreatedBy($this->getUser()->getEmail()); $this->em->flush(); }
        }

        return $this->json(['data' => ['id' => $id]], 201);
    }

    #[Route('/sponsors/{id}', methods: ['PUT'])]
    public function update(string $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!\is_array($data)) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }

        $command = new UpdateSponsorCommand(
            id: $id,
            name: $data['name'] ?? null,
            logoUrl: array_key_exists('logoUrl', $data) ? $data['logoUrl'] : null,
            website: array_key_exists('website', $data) ? $data['website'] : null,
            tier: $data['tier'] ?? null,
            isActive: $data['isActive'] ?? null,
            sortOrder: $data['sortOrder'] ?? null,
            message: array_key_exists('message', $data) ? $data['message'] : null,
        );

        $this->commandBus->dispatch($command);

        $orm = $this->em->getRepository(OrmSponsor::class)->find($id);
        if ($orm) { $orm->setUpdatedBy($this->getUser()->getEmail()); $this->em->flush(); }

        return $this->json(['data' => ['updated' => true]]);
    }

    #[Route('/sponsors/{id}/logo', methods: ['POST'])]
    public function uploadLogo(string $id, Request $request): JsonResponse
    {
        $file = $request->files->get('file');
        if (!$file || !$file->isValid()) {
            return $this->json(['error' => 'No file uploaded or invalid'], 400);
        }

        $envelope = $this->commandBus->dispatch(new UploadSponsorLogoCommand(
            sponsorId: $id,
            originalName: $file->getClientOriginalName(),
            mimeType: $file->getMimeType() ?? 'image/png',
            tmpPath: $file->getPathname(),
        ));

        $url = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json(['data' => ['id' => $id, 'logoUrl' => $url]]);
    }

    #[Route('/sponsors/{id}', methods: ['DELETE'])]
    public function delete(string $id): JsonResponse
    {
        $this->commandBus->dispatch(new DeleteSponsorCommand(id: $id));

        return $this->json(null, 204);
    }
}
