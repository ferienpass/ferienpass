<?php

declare(strict_types=1);

/*
 * This file is part of the Ferienpass package.
 *
 * (c) Richard Henkenjohann <richard@ferienpass.online>
 *
 * For more information visit the project website <https://ferienpass.online>
 * or the documentation under <https://docs.ferienpass.online>.
 */

namespace Ferienpass\AdminBundle\Components;

use Doctrine\ORM\EntityManagerInterface;
use Ferienpass\CoreBundle\Entity\Attendance;
use Ferienpass\CoreBundle\Entity\Offer\OfferInterface;
use Ferienpass\CoreBundle\Entity\OfferLog;
use Ferienpass\CoreBundle\Entity\Participant;
use Ferienpass\CoreBundle\Entity\ParticipantLog;
use Ferienpass\CoreBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\ValidatableComponentTrait;

#[AsLiveComponent(route: 'live_component_admin')]
class ListActivityPanel extends AbstractController
{
    use ComponentToolsTrait;
    use DefaultActionTrait;
    use ValidatableComponentTrait;

    #[LiveProp]
    public string $entity;

    #[LiveProp(hydrateWith: 'hydrateItem', dehydrateWith: 'dehydrateItem')]
    public ?object $item = null;

    #[LiveProp(writable: true)]
    #[NotBlank]
    public string $newComment = '';

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    #[LiveListener('view')]
    public function open(#[LiveArg] int $id)
    {
        $this->item = $this->entityManager->getRepository($this->entity)->find($id);
        $this->newComment = '';

        $this->resetValidation();
        $this->dispatchBrowserEvent('admin:slideover:open');
    }

    public function activity()
    {
        return $this->item?->getActivity()->toArray();
    }

    #[LiveAction]
    public function comment(EntityManagerInterface $em)
    {
        $this->validate();

        $user = $this->getUser();
        if (!$user instanceof User) {
            return;
        }

        $comment = match (true) {
            $this->item instanceof Attendance => new ParticipantLog($this->item->getParticipant(), $user, attendance: $this->item, comment: $this->newComment),
            $this->item instanceof Participant => new ParticipantLog($this->item, $user, comment: $this->newComment),
            $this->item instanceof OfferInterface => new OfferLog($this->item, $user, comment: $this->newComment),
            default => null,
        };

        if (null === $comment) {
            return;
        }

        $em->persist($comment);
        $em->flush();

        $this->newComment = '';
        $this->resetValidation();

        $this->emit('admin_list:changed');

        // $this->addFlash('success', 'Post saved!');
    }

    public function dehydrateItem(?object $item): int|null
    {
        return $item?->getId();
    }

    public function hydrateItem(?int $data): ?object
    {
        if (null === $data) {
            return null;
        }

        return $this->entityManager->getRepository($this->entity)->find($data);
    }
}
