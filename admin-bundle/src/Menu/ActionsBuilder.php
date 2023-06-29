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

namespace Ferienpass\AdminBundle\Menu;

use Ferienpass\CoreBundle\Entity\Attendance;
use Ferienpass\CoreBundle\Entity\Host;
use Ferienpass\CoreBundle\Entity\Offer;
use Ferienpass\CoreBundle\Entity\Participant;
use Ferienpass\CoreBundle\Entity\Payment;
use Ferienpass\CoreBundle\Repository\EditionRepository;
use Ferienpass\CoreBundle\Repository\PaymentRepository;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ActionsBuilder
{
    public function __construct(private FactoryInterface $factory, private AuthorizationCheckerInterface $authorizationChecker, private EditionRepository $editionRepository, private PaymentRepository $paymentRepository, private EventDispatcherInterface $dispatcher)
    {
    }

    public function actions(array $options = []): ItemInterface
    {
        $item = $options['item'] ?? null;
        if (null === $item) {
            throw new \InvalidArgumentException('The list item is not available');
        }

        $menu = $this->factory->createItem('root');

        if ($item instanceof Offer) {
            $this->offers($menu, $item);

            return $menu;
        }

        if ($item instanceof Participant) {
            $this->participants($menu, $item);

            return $menu;
        }

        if ($item instanceof Host) {
            $this->hosts($menu, $item);

            return $menu;
        }

        if ($item instanceof Attendance) {
            $this->attendances($menu, $item);

            return $menu;
        }

        if ($item instanceof Payment) {
            $this->payments($menu, $item);

            return $menu;
        }

        return $menu;
    }

    private function offers(ItemInterface $root, Offer $item)
    {
        $root->addChild('view', [
            'label' => 'offers.action.view',
            'route' => 'admin_offer_show',
            'routeParameters' => ['id' => $item->getId(), 'edition' => $item->getEdition()->getAlias()],
            'display' => $this->isGranted('view', $item),
            'extras' => ['icon' => 'pencil-solid'],
        ]);
        $root->addChild('edit', [
            'label' => 'offers.action.edit',
            'route' => 'admin_offers_edit',
            'routeParameters' => ['id' => $item->getId(), 'edition' => $item->getEdition()->getAlias()],
            'display' => $this->isGranted('edit', $item),
            'extras' => ['icon' => 'pencil-solid'],
        ]);

        $root->addChild('newVariant', [
            'label' => 'offers.action.newVariant',
            'route' => 'admin_offer_new',
            'routeParameters' => ['source' => null === $item->getVariantBase() ? $item->getId() : $item->getVariantBase()?->getId(), 'act' => 'newVariant', 'edition' => $item->getEdition()->getAlias()],
            'display' => $this->isGranted('create', $item) && $this->isGranted('edit', $item),
            'extras' => ['icon' => 'calendar-solid'],
        ]);

        foreach ($this->editionRepository->findWithActiveTask('host_editing_stage') as $edition) {
            $root->addChild('copy'.$edition->getId(), [
                'label' => 'offers.action.copy',
                'route' => 'admin_offer_new',
                'routeParameters' => ['source' => $item->getId(), 'act' => 'copy', 'edition' => $edition->getAlias()],
                'display' => $this->isGranted('view', $item),
                'extras' => ['icon' => 'duplicate-solid', 'translation_params' => ['edition' => $edition->getName()]],
            ]);
        }

        $root->addChild('delete', [
            'label' => 'offers.action.delete',
            'route' => 'admin_offer_show',
            'routeParameters' => ['id' => $item->getId(), 'edition' => $item->getEdition()->getAlias()],
            'display' => $this->isGranted('delete', $item),
            'extras' => [
                'method' => 'delete',
                'icon' => 'trash-solid',
            ],
        ]);

        if (!$item->isCancelled()
            && ((null === $edition = $item->getEdition()) || !$edition->getActiveTasks('show_offers')->isEmpty())) {
            $root->addChild('cancel', [
                'label' => 'offers.action.cancel',
                'route' => 'admin_offer_show',
                'routeParameters' => ['id' => $item->getId(), 'act' => 'cancel', 'edition' => $item->getEdition()->getAlias()],
                'display' => $this->isGranted('cancel', $item),
                'extras' => [
                    'method' => 'post',
                    'icon' => 'ban-solid',
                ],
            ]);
        }

        if ($item->isCancelled()) {
            $root->addChild('reactivate', [
                'label' => 'offers.action.reactivate',
                'route' => 'admin_offer_show',
                'routeParameters' => ['id' => $item->getId(), 'act' => 'relaunch', 'edition' => $item->getEdition()->getAlias()],
                'display' => $this->isGranted('reactivate', $item),
                'extras' => [
                    'method' => 'post',
                    'icon' => 'trash-solid',
                ],
            ]);
        }

        if ($item->isOnlineApplication()) {
            $root->addChild('participantList', [
                'label' => 'offers.action.participantList',
                'route' => 'admin_offer_attendances',
                'routeParameters' => ['id' => $item->getId()],
                'display' => $this->isGranted('participants.view', $item),
                'extras' => ['icon' => 'user-group-solid'],
            ]);
            $root->addChild('participantList2', [
                'label' => 'offers.action.participantList',
                'route' => 'admin_offer_applications',
                'routeParameters' => ['id' => $item->getId(), 'edition' => $item->getEdition()->getAlias()],
                'display' => $this->isGranted('participants.view', $item),
                'extras' => ['icon' => 'user-group-solid'],
            ]);
        }
    }

    private function participants(ItemInterface $root, Participant $item)
    {
        $root->addChild('attendances', [
            'label' => 'participants.action.attendances',
            'route' => 'admin_participants_attendances',
            'routeParameters' => ['id' => $item->getId()],
            'display' => $this->isGranted('view', $item),
            'extras' => ['icon' => 'pencil-solid'],
        ]);

        $root->addChild('edit', [
            'label' => 'participants.action.edit',
            'route' => 'admin_participants_edit',
            'routeParameters' => ['id' => $item->getId()],
            'display' => $this->isGranted('edit', $item),
            'extras' => ['icon' => 'pencil-solid'],
        ]);

        $payments = $this->paymentRepository->createQueryBuilder('pay')
            ->innerJoin('pay.items', 'i')
            ->innerJoin('i.attendance', 'a')
            ->innerJoin('a.participant', 'p')
            ->where('p.id = :id')
            ->setParameter('id', $item->getId())
            ->getQuery()
            ->getResult()
        ;

        /** @var Payment $payment */
        foreach ($payments as $payment) {
            $root->addChild('show_payment.'.$payment->getId(), [
                'label' => 'participants.action.show_payment',
                'route' => 'admin_payments_receipt',
                'routeParameters' => ['id' => $payment->getId()],
                // 'display' => $this->isGranted('view', $payment),
                'extras' => ['icon' => 'pencil-solid', 'translation_params' => ['%number%' => $payment->getReceiptNumber()]],
            ]);
        }
    }

    private function hosts(ItemInterface $root, Host $item)
    {
        $root->addChild('show', [
            'label' => 'hosts.action.show',
            'route' => 'admin_hosts_show',
            'routeParameters' => ['alias' => $item->getAlias()],
            'display' => $this->isGranted('view', $item),
        ]);

        $root->addChild('edit', [
            'label' => 'hosts.action.edit',
            'route' => 'admin_hosts_edit',
            'routeParameters' => ['alias' => $item->getAlias()],
            'display' => $this->isGranted('edit', $item),
            'extras' => ['icon' => 'pencil-solid'],
        ]);
    }

    private function attendances(ItemInterface $root, Attendance $item)
    {
        $root->addChild('offer', [
            'label' => 'attendance.action.offer',
            'route' => 'admin_offer_applications',
            'routeParameters' => ['id' => $item->getOffer()->getId(), 'edition' => $item->getOffer()->getEdition()->getAlias()],
            'display' => $this->isGranted('view', $item->getOffer()),
        ]);
    }

    private function payments(ItemInterface $root, Payment $item)
    {
        $root->addChild('offer', [
            'label' => 'payments.action.receipt',
            'route' => 'admin_payments_receipt',
            'routeParameters' => ['id' => $item->getId()],
            //   'display' => $this->isGranted('view', $item->getOffer()),
            'extras' => ['icon' => 'pencil-solid'],
        ]);
    }

    private function isGranted(string $attribute, object $item): bool
    {
        return $this->authorizationChecker->isGranted($attribute, $item);
    }
}
