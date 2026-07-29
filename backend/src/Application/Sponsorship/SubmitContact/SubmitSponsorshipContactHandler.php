<?php

declare(strict_types=1);

namespace App\Application\Sponsorship\SubmitContact;

use App\Domain\Sponsorship\Contact\Entity\SponsorshipContact;
use App\Domain\Sponsorship\Contact\Repository\SponsorshipContactRepositoryInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class SubmitSponsorshipContactHandler
{
    public function __construct(
        private SponsorshipContactRepositoryInterface $repository,
    ) {
    }

    public function __invoke(SubmitSponsorshipContactCommand $command): array
    {
        $contact = new SponsorshipContact(
            id: Uuid::uuid4()->toString(),
            name: $command->name,
            company: $command->company,
            email: $command->email,
            phone: $command->phone,
            message: $command->message,
        );

        $this->repository->save($contact);

        return [
            'id' => $contact->id(),
            'message' => 'Solicitud recibida. Nos pondremos en contacto contigo pronto.',
        ];
    }
}
