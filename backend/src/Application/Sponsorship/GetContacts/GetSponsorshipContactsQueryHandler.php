<?php

declare(strict_types=1);

namespace App\Application\Sponsorship\GetContacts;

use App\Domain\Sponsorship\Contact\Repository\SponsorshipContactRepositoryInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class GetSponsorshipContactsQueryHandler
{
    public function __construct(
        private SponsorshipContactRepositoryInterface $repository,
    ) {
    }

    public function __invoke(GetSponsorshipContactsQuery $query): array
    {
        $contacts = $this->repository->findAll();

        return array_map(function ($contact) {
            return [
                'id' => $contact->id(),
                'name' => $contact->name(),
                'company' => $contact->company(),
                'email' => $contact->email(),
                'phone' => $contact->phone(),
                'message' => $contact->message(),
                'createdAt' => $contact->createdAt()->format('Y-m-d H:i:s'),
            ];
        }, $contacts);
    }
}
