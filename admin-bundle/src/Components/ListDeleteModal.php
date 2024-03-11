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
use Ferienpass\CoreBundle\Entity\Offer\OfferInterface;
use Ferienpass\CoreBundle\Entity\Participant;
use Ferienpass\CoreBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(route: 'live_component_admin')]
class ListDeleteModal extends AbstractController
{
    use ComponentToolsTrait;
    use DefaultActionTrait;

    #[LiveProp]
    public string $type;

    #[LiveProp(hydrateWith: 'hydrateItem', dehydrateWith: 'dehydrateItem')]
    public ?object $item = null;

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    #[LiveListener('delete')]
    public function open(#[LiveArg] int $id, #[LiveArg] string $class)
    {
        $this->item = $this->entityManager->getRepository($class)->find($id);
        $this->dispatchBrowserEvent('admin:modal:open');
    }

    #[LiveAction]
    public function delete(EntityManagerInterface $entityManager)
    {
        $this->denyAccessUnlessGranted('delete', $this->item);

        // TODO Refactor using a Facade
        if ($this->item instanceof Participant) {
            /** @var $attendance */
            foreach ($this->item->getAttendances() as $attendance) {
                foreach ($attendance->getPaymentItems() as $paymentItem) {
                    $paymentItem->removeAttendanceAssociation();
                }
            }

            $entityManager->flush();
        }
        if ($this->item instanceof OfferInterface) {
            /** @var $attendance */
            foreach ($this->item->getAttendances() as $attendance) {
                foreach ($attendance->getPaymentItems() as $paymentItem) {
                    $paymentItem->removeAttendanceAssociation();
                }
            }

            if ($this->item->hasVariants()) {
                $variants = $this->item->getVariants()->toArray();
                $variants[0]->setVariantBase(null);

                for ($i = 1; $i < \count($variants); ++$i) {
                    $variants[$i]->setVariantBase($variants[0]);
                }
            } elseif (!$this->item->isVariantBase()) {
                $this->item->setVariantBase(null);
            }

            $entityManager->flush();
        }
        if ($this->item instanceof User) {
            foreach ($this->item->getParticipants() as $participant) {
                /** @var $attendance */
                foreach ($participant->getAttendances() as $attendance) {
                    foreach ($attendance->getPaymentItems() as $paymentItem) {
                        $paymentItem->removeAttendanceAssociation();
                    }
                }
            }

            $entityManager->flush();
        }

        $entityManager->remove($this->item);
        $entityManager->flush();

        $this->dispatchBrowserEvent('admin:modal:close');
        $this->emit('admin_list:changed');

        $this->item = null;
    }

    #[LiveAction]
    public function cancel()
    {
        $this->dispatchBrowserEvent('admin:modal:close');
        $this->item = null;
    }

    public function dehydrateItem(?object $item): array|null
    {
        if (null === $item) {
            return null;
        }

        return [$item::class, $item->getId()];
    }

    public function hydrateItem(?array $data): ?object
    {
        if (null === $data) {
            return null;
        }
        [$class, $id] = $data;

        return $this->entityManager->getRepository($class)->find($id);
    }
}
