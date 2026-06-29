<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\EventSubscriber;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Bundle\SecurityBundle\Security;

#[AsDoctrineListener(event: Events::prePersist)]
#[AsDoctrineListener(event: Events::preUpdate)]
final class AuditSubscriber
{
    public function __construct(
        private Security $security,
    ) {
    }

    public function prePersist(PrePersistEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!method_exists($entity, 'setCreatedBy')) {
            return;
        }
        $user = $this->security->getUser();
        if ($user !== null && method_exists($user, 'getEmail')) {
            $entity->setCreatedBy($user->getEmail());
        }
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!method_exists($entity, 'setUpdatedBy')) {
            return;
        }
        $user = $this->security->getUser();
        if ($user !== null && method_exists($user, 'getEmail')) {
            $entity->setUpdatedBy($user->getEmail());
        }
    }
}
