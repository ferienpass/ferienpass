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

use Ferienpass\AdminBundle\Controller\Page\AccountsController;
use Ferienpass\CoreBundle\Entity\Attendance;
use Ferienpass\CoreBundle\Entity\Edition;
use Ferienpass\CoreBundle\Entity\Host;
use Ferienpass\CoreBundle\Entity\Offer;
use Ferienpass\CoreBundle\Entity\Participant;
use Ferienpass\CoreBundle\Entity\Payment;
use Ferienpass\CoreBundle\Entity\User;
use Ferienpass\CoreBundle\Repository\EditionRepository;
use Ferienpass\CoreBundle\Repository\PaymentRepository;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ActionsBuilder
{
    public function __construct(private readonly FactoryInterface $factory, private readonly AuthorizationCheckerInterface $authorizationChecker, private readonly EditionRepository $editionRepository, private readonly PaymentRepository $paymentRepository, private readonly EventDispatcherInterface $dispatcher)
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

        if ($item instanceof Edition) {
            $this->editions($menu, $item);

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

        if ($item instanceof User) {
            $this->accounts($menu, $item);

            return $menu;
        }

        return $menu;
    }

    private function offers(ItemInterface $root, Offer $item)
    {
        $root->addChild('edit', [
            'label' => 'offers.action.edit',
            'route' => 'admin_offers_edit',
            'routeParameters' => array_filter(['id' => $item->getId(), 'edition' => $item->getEdition()?->getAlias()]),
            'display' => $this->isGranted('edit', $item),
            'extras' => ['icon' => 'pencil-solid'],
        ]);

        $root->addChild('proof', [
            'label' => 'offers.action.proof',
            'route' => 'admin_offer_proof',
            'routeParameters' => array_filter(['id' => $item->getId(), 'edition' => $item->getEdition()?->getAlias()]),
            'display' => $this->isGranted('view', $item),
            'extras' => ['icon' => 'pencil-solid'],
        ]);

        $root->addChild('newVariant', [
            'label' => 'offers.action.newVariant',
            'route' => 'admin_offers_new_variant',
            'routeParameters' => array_filter(['id' => null === $item->getVariantBase() ? $item->getId() : $item->getVariantBase()?->getId(), 'edition' => $item->getEdition()?->getAlias()]),
            'display' => $this->isGranted('create', $item) && $this->isGranted('edit', $item),
            'extras' => ['icon' => 'calendar-solid'],
        ]);

        if ($this->isGranted('ROLE_ADMIN')) {
            $root->addChild('copy', [
                'label' => 'offers.action.copy',
                'route' => 'admin_offers_copy',
                'routeParameters' => array_filter(['id' => $item->getId(), 'edition' => $item->getEdition()?->getAlias()]),
                'display' => $this->isGranted('view', $item),
                'extras' => ['icon' => 'duplicate-solid'],
            ]);
        } else {
            foreach ($this->editionRepository->findWithActiveTask('host_editing_stage') as $edition) {
                $root->addChild('copy'.$edition->getId(), [
                    'label' => 'offers.action.copyTo',
                    'route' => 'admin_offers_copy',
                    'routeParameters' => ['id' => $item->getId(), 'edition' => $edition->getAlias()],
                    'display' => $this->isGranted('view', $item),
                    'extras' => ['icon' => 'duplicate-solid', 'translation_params' => ['edition' => $edition->getName()]],
                ]);
            }
        }

        //        $root->addChild('delete', [
        //            'label' => 'offers.action.delete',
        //            'route' => 'admin_offer_show',
        //            'routeParameters' => array_filter(['id' => $item->getId(), 'edition' => $item->getEdition()?->getAlias()],
        //            'display' => $this->isGranted('delete', $item),
        //            'extras' => [
        //                'method' => 'delete',
        //                'icon' => 'trash-solid',
        //            ],
        //        ]);

        if ($item->isOnlineApplication()) {
            $root->addChild('participantList', [
                'label' => 'offers.action.participantList',
                'route' => 'admin_offer_attendances',
                'routeParameters' => array_filter(['id' => $item->getId(), 'edition' => $item->getEdition()?->getAlias()]),
                'display' => $this->isGranted('participants.view', $item),
                'extras' => ['icon' => 'user-group-solid'],
            ]);
            $root->addChild('participantList2', [
                'label' => 'offers.action.participantList',
                'route' => 'admin_offer_applications',
                'routeParameters' => array_filter(['id' => $item->getId(), 'edition' => $item->getEdition()?->getAlias()]),
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
        $root->addChild('edit', [
            'label' => 'hosts.action.edit',
            'route' => 'admin_hosts_edit',
            'routeParameters' => ['alias' => $item->getAlias()],
            'display' => $this->isGranted('edit', $item),
            'extras' => ['icon' => 'pencil-solid'],
        ]);
    }

    private function editions(ItemInterface $root, Edition $item)
    {
        $root->addChild('edit', [
            'label' => 'editions.action.edit',
            'route' => 'admin_editions_edit',
            'routeParameters' => ['alias' => $item->getAlias()],
            'display' => $this->isGranted('edit', $item),
            'extras' => ['icon' => 'pencil-solid'],
        ]);

        $root->addChild('stats', [
            'label' => 'editions.action.stats',
            'route' => 'admin_editions_stats',
            'routeParameters' => ['alias' => $item->getAlias()],
            'display' => $this->isGranted('stats', $item),
            'extras' => ['icon' => 'chart-pie.mini'],
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
        $root->addChild('receipt', [
            'label' => 'payments.action.receipt',
            'route' => 'admin_payments_receipt',
            'routeParameters' => ['id' => $item->getId()],
            'extras' => ['icon' => 'pencil-solid'],
        ]);
    }

    private function accounts(ItemInterface $root, User $item)
    {
        $root->addChild('edit', [
            'label' => 'accounts.action.edit',
            'route' => 'admin_accounts_edit',
            'routeParameters' => ['id' => $item->getId(), 'role' => array_search($item->getRoles()[0], AccountsController::ROLES, true) ?: 'eltern'],
            'display' => $this->isGranted('edit', $item),
            'extras' => ['icon' => 'pencil-solid'],
        ]);

        $root->addChild('impersonate', [
            'label' => 'accounts.action.impersonate',
            'route' => false ? 'user_account' : 'admin_index',
            'routeParameters' => ['_switch_user' => $item->getUserIdentifier()],
            'display' => $this->isGranted('ROLE_ALLOWED_TO_SWITCH'),
            'extras' => ['icon' => 'logout-filled', 'translation_params' => ['user' => $item->getUserIdentifier()]],
        ]);
    }

    private function isGranted(string $attribute, object $item = null): bool
    {
        return $this->authorizationChecker->isGranted($attribute, $item);
    }
}
