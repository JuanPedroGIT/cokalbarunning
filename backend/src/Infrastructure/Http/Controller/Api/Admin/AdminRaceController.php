<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Api\Admin;

use App\Application\Race\Create\CreateRaceEditionCommand;
use App\Application\Race\Delete\DeleteRaceEditionCommand;
use App\Application\Race\DeleteImage\DeleteRaceEditionImageCommand;
use App\Application\Race\Update\UpdateRaceEditionCommand;
use App\Application\Race\UploadImage\UploadRaceEditionImageCommand;
use App\Domain\Media\Port\StoragePort;
use App\Entity\RaceEdition as OrmRaceEdition;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1/admin')]
class AdminRaceController extends AbstractController
{
    public function __construct(
        private MessageBusInterface $commandBus,
        private StoragePort $storage,
        private EntityManagerInterface $em,
    ) {
    }

    #[Route('/editions', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!\is_array($data)) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }

        $command = new CreateRaceEditionCommand(
            year: $data['year'],
            name: $data['name'],
            description: $data['description'] ?? '',
            date: $data['date'],
            location: $data['location'] ?? '',
            isActive: $data['isActive'] ?? true,
            showBibSearch: $data['showBibSearch'] ?? false,
            posterUrl: $data['posterUrl'] ?? null,
            registrationUrl: $data['registrationUrl'] ?? null,
            shirtUrl: $data['shirtUrl'] ?? null,
            trophyUrl: $data['trophyUrl'] ?? null,
        );

        $envelope = $this->commandBus->dispatch($command);
        $handled = $envelope->last(HandledStamp::class);
        $id = $handled ? $handled->getResult() : null;

        if ($id) {
            $orm = $this->em->getRepository(OrmRaceEdition::class)->find($id);
            if ($orm) { $orm->setCreatedBy($this->getUser()->getEmail()); $this->em->flush(); }
        }

        return $this->json(['data' => ['id' => $id, 'created' => true]], 201);
    }

    #[Route('/editions/{id}', methods: ['PUT'])]
    public function update(string $id, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!\is_array($data)) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }

        $command = new UpdateRaceEditionCommand(
            id: $id,
            year: $data['year'] ?? null,
            name: $data['name'] ?? null,
            description: $data['description'] ?? null,
            date: $data['date'] ?? null,
            location: $data['location'] ?? null,
            isActive: $data['isActive'] ?? null,
            showBibSearch: array_key_exists('showBibSearch', $data) ? (bool) $data['showBibSearch'] : null,
            posterUrl: array_key_exists('posterUrl', $data) ? $this->normalizePath($data['posterUrl']) : null,
            registrationUrl: array_key_exists('registrationUrl', $data) ? ($data['registrationUrl'] ?? '') : null,
            shirtUrl: array_key_exists('shirtUrl', $data) ? $this->normalizePath($data['shirtUrl']) : null,
            trophyUrl: array_key_exists('trophyUrl', $data) ? $this->normalizePath($data['trophyUrl']) : null,
            inscriptionInfo: array_key_exists('inscriptionInfo', $data) ? ($data['inscriptionInfo'] ?? '') : null,
            solidarityCause: array_key_exists('solidarityCause', $data) ? ($data['solidarityCause'] ?? '') : null,
            solidarityUrl: array_key_exists('solidarityUrl', $data) ? ($data['solidarityUrl'] ?? '') : null,
        );

        $this->commandBus->dispatch($command);

        $orm = $this->em->getRepository(OrmRaceEdition::class)->find($id);
        if ($orm) { $orm->setUpdatedBy($this->getUser()->getEmail()); $this->em->flush(); }

        return $this->json(['data' => ['updated' => true]]);
    }

    private function normalizePath(mixed $value): string
    {
        if ($value === null || $value === '') return '';
        $base = rtrim((string) $this->storage->url(''), '/');
        return str_starts_with((string) $value, $base)
            ? substr((string) $value, strlen($base) + 1)
            : (string) $value;
    }

    #[Route('/editions/{id}/poster', methods: ['POST'])]
    public function uploadPoster(string $id, Request $request): JsonResponse
    {
        return $this->handleImageUpload($id, $request, 'poster');
    }

    #[Route('/editions/{id}/shirt', methods: ['POST'])]
    public function uploadShirt(string $id, Request $request): JsonResponse
    {
        return $this->handleImageUpload($id, $request, 'shirt');
    }

    #[Route('/editions/{id}/trophy', methods: ['POST'])]
    public function uploadTrophy(string $id, Request $request): JsonResponse
    {
        return $this->handleImageUpload($id, $request, 'trophy');
    }

    private function handleImageUpload(string $id, Request $request, string $type): JsonResponse
    {
        $file = $request->files->get('file');
        if (!$file || !$file->isValid()) {
            return $this->json(['error' => 'No file uploaded or invalid'], 400);
        }

        $envelope = $this->commandBus->dispatch(new UploadRaceEditionImageCommand(
            editionId: $id,
            type: $type,
            originalName: $file->getClientOriginalName(),
            mimeType: $file->getMimeType() ?? 'image/jpeg',
            tmpPath: $file->getPathname(),
        ));

        $url = $envelope->last(HandledStamp::class)?->getResult();

        return $this->json(['data' => [
            'id' => $id,
            match ($type) {
                'poster' => 'posterUrl',
                'shirt' => 'shirtUrl',
                'trophy' => 'trophyUrl',
            } => $url,
        ]]);
    }

    #[Route('/editions/{id}/poster', methods: ['DELETE'])]
    public function deletePoster(string $id): JsonResponse
    {
        $this->commandBus->dispatch(new DeleteRaceEditionImageCommand(editionId: $id, type: 'poster'));
        return $this->json(['data' => ['deleted' => true]]);
    }

    #[Route('/editions/{id}/shirt', methods: ['DELETE'])]
    public function deleteShirt(string $id): JsonResponse
    {
        $this->commandBus->dispatch(new DeleteRaceEditionImageCommand(editionId: $id, type: 'shirt'));
        return $this->json(['data' => ['deleted' => true]]);
    }

    #[Route('/editions/{id}/trophy', methods: ['DELETE'])]
    public function deleteTrophy(string $id): JsonResponse
    {
        $this->commandBus->dispatch(new DeleteRaceEditionImageCommand(editionId: $id, type: 'trophy'));
        return $this->json(['data' => ['deleted' => true]]);
    }

    #[Route('/editions/{id}', methods: ['DELETE'])]
    public function delete(string $id): JsonResponse
    {
        $this->commandBus->dispatch(new DeleteRaceEditionCommand(id: $id));

        return $this->json(null, 204);
    }
}
