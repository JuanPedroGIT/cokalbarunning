<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Mapper;

use App\Domain\SocialPublishing\Entity\SocialPublishLog as DomainSocialPublishLog;
use App\Entity\SocialPublishLog as OrmSocialPublishLog;

final class SocialPublishLogMapper
{
    public function toDomain(OrmSocialPublishLog $orm): DomainSocialPublishLog
    {
        return new DomainSocialPublishLog(
            id: $orm->getId(),
            postId: $orm->getPostId(),
            network: $orm->getNetwork(),
            status: $orm->getStatus(),
            createdAt: $orm->getCreatedAt(),
            publishedAt: $orm->getPublishedAt(),
            externalUrl: $orm->getExternalUrl(),
            publishedBy: $orm->getPublishedBy(),
        );
    }

    public function toOrm(DomainSocialPublishLog $domain, ?OrmSocialPublishLog $orm = null): OrmSocialPublishLog
    {
        $target = $orm ?? new OrmSocialPublishLog();
        $target->setId($domain->id());
        $target->setPostId($domain->postId());
        $target->setNetwork($domain->network());
        $target->setStatus($domain->status());
        $target->setPublishedAt($domain->publishedAt());
        $target->setExternalUrl($domain->externalUrl());
        $target->setPublishedBy($domain->publishedBy());

        if ($orm === null) {
            $target->setCreatedAt($domain->createdAt());
        }

        return $target;
    }
}
