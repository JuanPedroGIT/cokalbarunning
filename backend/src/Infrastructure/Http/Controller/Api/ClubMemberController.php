<?php

declare(strict_types=1);

namespace App\Infrastructure\Http\Controller\Api;

use App\Application\Club\Query\GetClubMembersQuery;
use App\Application\Club\Response\ClubMemberResponseDto;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/v1')]
class ClubMemberController extends AbstractController
{
    public function __construct(private MessageBusInterface $queryBus)
    {
    }

    #[Route('/club-members', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $envelope = $this->queryBus->dispatch(new GetClubMembersQuery(onlyActive: true));
        /** @var ClubMemberResponseDto[] $dtos */
        $dtos = $envelope->last(HandledStamp::class)?->getResult() ?? [];

        return $this->json(['data' => ClubMemberResponseDto::listToArray($dtos)]);
    }
}
