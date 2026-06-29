<?php

declare(strict_types=1);

namespace App\Application\SocialPublishing\Query;

use App\Application\SocialPublishing\Response\SocialPublishLogResponseDto;
use App\Domain\SocialPublishing\Repository\SocialPublishLogRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class GetSocialPublishLogsQueryHandler
{
    public function __construct(
        private SocialPublishLogRepositoryInterface $repository,
    ) {
    }

    public function __invoke(GetSocialPublishLogsQuery $query): array
    {
        $logs = $this->repository->findAll();

        return SocialPublishLogResponseDto::fromDomainList($logs);
    }
}
