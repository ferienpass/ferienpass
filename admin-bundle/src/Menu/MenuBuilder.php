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

use Ferienpass\AdminBundle\Event\MenuEvent;
use Ferienpass\CoreBundle\Entity\Offer;
use Ferienpass\CoreBundle\Entity\User;
use Ferienpass\CoreBundle\Repository\EditionRepository;
use Ferienpass\CoreBundle\Repository\HostRepository;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Logout\LogoutUrlGenerator;

class MenuBuilder
{
    public function __construct(private readonly FactoryInterface $factory, private readonly LogoutUrlGenerator $logoutUrlGenerator, private readonly AuthorizationCheckerInterface $authorizationChecker, private readonly RequestStack $requestStack, private readonly EditionRepository $editionRepository, private readonly Security $security, private readonly HostRepository $hostRepository, private readonly EventDispatcherInterface $dispatcher)
    {
    }

    public function primaryNavigation(): ItemInterface
    {
        $menu = $this->factory->createItem('root');

        $edition = $this->editionRepository->findDefaultForHost();
        if (null !== $edition) {
            $menu->addChild('offers.title', [
                'route' => 'admin_offers_index',
                'routeParameters' => [
                    'edition' => $edition->getAlias(),
                ],
            ]);
        }

        if (!$this->isGranted('ROLE_ADMIN')) {
            $menu->addChild('profile.title', [
                'route' => 'admin_profile_index',
            ]);
        }

        if ($this->isGranted('ROLE_ADMIN')) {
            $menu->addChild('participants.title', [
                'route' => 'admin_participants_index',
            ]);
            $menu->addChild('hosts.title', [
                'route' => 'admin_hosts_index',
            ]);
            $menu->addChild('payments.title', [
                'route' => 'admin_payments_index',
            ]);
            $menu->addChild('Accounts', [
                'route' => 'admin_accounts_index',
            ]);
            $menu->addChild('Werkzeuge & Einstellungen', [
                'route' => 'admin_tools',
            ]);
        }

        return $menu;
    }

    public function userNavigation(): ItemInterface
    {
        $request = $this->requestStack->getMainRequest();
        $menu = $this->factory->createItem('root');

        $menu->addChild('user.title', [
            'route' => 'admin_user_index',
            'current' => null !== $request && 'host_change-host_personal_data' === $request->attributes->get('_route'),
            'extras' => ['icon' => 'user-circle-filled'],
        ]);

        $menu->addChild('user.password.title', [
            'route' => 'admin_password',
            'current' => null !== $request && 'host_change-password' === $request->attributes->get('_route'),
            'extras' => ['icon' => 'lock-closed-filled'],
        ]);

        $menu->addChild('Abmelden', [
            'uri' => $this->logoutUrlGenerator->getLogoutUrl('contao_frontend'),
            'extras' => ['icon' => 'logout-filled'],
        ]);

        return $menu;
    }

    public function offerActions(array $options = []): ItemInterface
    {
        $offer = $options['offer'] ?? null;
        if (!$offer instanceof Offer) {
            throw new \InvalidArgumentException('Pass "offer" as an option');
        }

        $menu = $this->factory->createItem('offerActions');

        $menu->addChild('view', [
            'label' => 'offers.action.view',
            'route' => 'admin_offer_show',
            'routeParameters' => ['id' => $offer->getId(), 'edition' => $offer->getEdition()->getAlias()],
            'display' => $this->isGranted('view', $offer),
            'extras' => ['icon' => 'pencil-solid'],
        ]);
        $menu->addChild('edit', [
            'label' => 'offers.action.edit',
            'route' => 'admin_offers_edit',
            'routeParameters' => ['id' => $offer->getId(), 'edition' => $offer->getEdition()->getAlias()],
            'display' => $this->isGranted('edit', $offer),
            'extras' => ['icon' => 'pencil-solid'],
        ]);

        $menu->addChild('newVariant', [
            'label' => 'offers.action.newVariant',
            'route' => 'admin_offers_new',
            'routeParameters' => ['source' => null === $offer->getVariantBase() ? $offer->getId() : $offer->getVariantBase()?->getId(), 'act' => 'newVariant', 'edition' => $offer->getEdition()->getAlias()],
            'display' => $this->isGranted('create', $offer) && $this->isGranted('edit', $offer),
            'extras' => ['icon' => 'calendar-solid'],
        ]);

        foreach ($this->editionRepository->findWithActiveTask('host_editing_stage') as $edition) {
            $menu->addChild('copy'.$edition->getId(), [
                'label' => 'offers.action.copy',
                'route' => 'admin_offers_new',
                'routeParameters' => ['source' => $offer->getId(), 'act' => 'copy', 'edition' => $edition->getAlias()],
                'display' => $this->isGranted('view', $offer),
                'extras' => ['icon' => 'duplicate-solid', 'translation_params' => ['edition' => $edition->getName()]],
            ]);
        }

        $menu->addChild('delete', [
            'label' => 'offers.action.delete',
            'route' => 'admin_offer_show',
            'routeParameters' => ['id' => $offer->getId(), 'edition' => $offer->getEdition()->getAlias()],
            'display' => $this->isGranted('delete', $offer),
            'extras' => [
                'method' => 'delete',
                'icon' => 'trash-solid',
            ],
        ]);

        if (!$offer->isCancelled()
            && ((null === $edition = $offer->getEdition()) || !$edition->getActiveTasks('show_offers')->isEmpty())) {
            $menu->addChild('cancel', [
                'label' => 'offers.action.cancel',
                'route' => 'admin_offer_show',
                'routeParameters' => ['id' => $offer->getId(), 'act' => 'cancel', 'edition' => $offer->getEdition()->getAlias()],
                'display' => $this->isGranted('cancel', $offer),
                'extras' => [
                    'method' => 'post',
                    'icon' => 'ban-solid',
                ],
            ]);
        }

        if ($offer->isCancelled()) {
            $menu->addChild('reactivate', [
                'label' => 'offers.action.reactivate',
                'route' => 'admin_offer_show',
                'routeParameters' => ['id' => $offer->getId(), 'act' => 'relaunch', 'edition' => $offer->getEdition()->getAlias()],
                'display' => $this->isGranted('reactivate', $offer),
                'extras' => [
                    'method' => 'post',
                    'icon' => 'trash-solid',
                ],
            ]);
        }

        if ($offer->isOnlineApplication()) {
            $menu->addChild('participantList', [
                'label' => 'offers.action.participantList',
                'route' => 'admin_offer_attendances',
                'routeParameters' => ['id' => $offer->getId()],
                'display' => $this->isGranted('participants.view', $offer),
                'extras' => ['icon' => 'user-group-solid'],
            ]);
            $menu->addChild('participantList2', [
                'label' => 'offers.action.participantList',
                'route' => 'admin_offer_applications',
                'routeParameters' => ['id' => $offer->getId(), 'edition' => $offer->getEdition()->getAlias()],
                'display' => $this->isGranted('participants.view', $offer),
                'extras' => ['icon' => 'user-group-solid'],
            ]);
            $menu->addChild('participantList.pdf', [
                'label' => 'offers.action.participantListPdf',
                'route' => 'admin_offer_attendances',
                'routeParameters' => ['id' => $offer->getId(), '_suffix' => '.pdf'],
                'display' => $this->isGranted('participants.view', $offer),
                'extras' => ['icon' => 'user-group-solid'],
            ]);
        }

        $menuEvent = new MenuEvent($this->factory, $menu, $options);
        $this->dispatcher->dispatch($menuEvent);

        return $menu;
    }

    public function offerFilters(array $options = []): ItemInterface
    {
        $request = $this->requestStack->getMainRequest();
        $menu = $this->factory->createItem('root');

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return $menu;
        }

        $editionNav = $this->factory->createItem('edition');
        $hostNav = $this->factory->createItem('host');

        $editions = $this->editionRepository->findBy([], ['id' => 'DESC'], 5);
        $defaultEdition = $this->editionRepository->findDefaultForHost();
        foreach ($editions as $edition) {
            $editionNav->addChild((string) $edition->getAlias(), [
                'label' => $edition->getName(),
                'route' => 'admin_offers_index',
                'routeParameters' => ['edition' => $edition->getAlias()] + $request?->query->all() ?? [],
                'current' => $request?->query->has('edition')
                    ? $edition->getAlias() === $request?->query->get('edition')
                    : $edition->getId() === $defaultEdition?->getId(),
            ]);
        }

        $edition = $this->editionRepository->findDefaultForHost();
        foreach ($this->hostRepository->findByUser($user) as $host) {
            $hostNav->addChild((string) $host->getAlias(), [
                'label' => $host->getName(),
                'route' => 'admin_offers_index',
                'routeParameters' => ['host' => $host->getAlias(), 'edition' => $edition->getAlias()] + $request?->query->all() ?? [],
                'current' => !$request?->query->has('host') || $host->getAlias() === $request?->query->get('host'),
            ]);
        }

        $menu->addChild($editionNav);
        $menu->addChild($hostNav);

        return $menu;
    }

    public function participantListActions(array $options = []): ItemInterface
    {
        $offer = $options['offer'] ?? null;
        if (!$offer instanceof Offer) {
            throw new \InvalidArgumentException('Pass "offer" as option');
        }

        $menu = $this->factory->createItem('root');

        $menu->addChild('pdf', [
            'label' => 'offers.action.participantList',
            'route' => 'host_participant_list',
            'routeParameters' => ['id' => $offer->getId(), '_suffix' => '.pdf'],
            'extras' => [
                'icon' => 'document-download-solid',
            ],
        ]);

        if (!$offer->isCancelled()
            && ((null === $edition = $offer->getEdition()) || !$edition->getActiveTasks('show_offers')->isEmpty())) {
            $menu->addChild('cancel', [
                'label' => 'offers.action.cancel',
                'route' => 'admin_offer_show',
                'routeParameters' => ['id' => $offer->getId(), 'act' => 'cancel'],
                'display' => $this->isGranted('cancel', $offer),
                'extras' => [
                    'method' => 'post',
                    'icon' => 'ban-solid',
                ],
            ]);
        }

        if ($offer->isCancelled()) {
            $menu->addChild('reactivate', [
                'label' => 'offers.action.reactivate',
                'route' => 'admin_offer_show',
                'routeParameters' => ['id' => $offer->getId(), 'act' => 'relaunch', 'edition' => $offer->getEdition()->getAlias()],
                'display' => $this->isGranted('reactivate', $offer),
                'extras' => [
                    'method' => 'post',
                    'icon' => 'trash-solid',
                ],
            ]);
        }

        return $menu;
    }

    private function isGranted(string $attribute, mixed $subject = null): bool
    {
        return $this->authorizationChecker->isGranted($attribute, $subject);
    }
}
