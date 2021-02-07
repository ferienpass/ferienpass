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

namespace Ferienpass\HostPortalBundle\Menu;

use Contao\FrontendUser;
use Ferienpass\CoreBundle\Entity\Edition;
use Ferienpass\CoreBundle\Entity\Offer;
use Ferienpass\CoreBundle\Repository\EditionRepository;
use Ferienpass\CoreBundle\Repository\HostRepository;
use Ferienpass\HostPortalBundle\Event\MenuEvent;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Logout\LogoutUrlGenerator;

class MenuBuilder
{
    private FactoryInterface $factory;
    private LogoutUrlGenerator $logoutUrlGenerator;
    private AuthorizationCheckerInterface $authorizationChecker;
    private RequestStack $requestStack;
    private EditionRepository $editionRepository;
    private Security $security;
    private HostRepository $hostRepository;
    private EventDispatcherInterface $dispatcher;

    public function __construct(
        FactoryInterface $factory,
        LogoutUrlGenerator $logoutUrlGenerator,
        AuthorizationCheckerInterface $authorizationChecker,
        RequestStack $requestStack,
        EditionRepository $editionRepository,
        Security $security,
        HostRepository $hostRepository,
        EventDispatcherInterface $dispatcher
    ) {
        $this->factory = $factory;
        $this->logoutUrlGenerator = $logoutUrlGenerator;
        $this->authorizationChecker = $authorizationChecker;
        $this->requestStack = $requestStack;
        $this->editionRepository = $editionRepository;
        $this->security = $security;
        $this->hostRepository = $hostRepository;
        $this->dispatcher = $dispatcher;
    }

    public function userNavigation(): ItemInterface
    {
        $request = $this->requestStack->getMasterRequest();
        $menu = $this->factory->createItem('root');

        $menu->addChild('Persönliche Daten', [
            'route' => 'host_personal_data',
            'current' => null !== $request && 'host_change-host_personal_data' === $request->attributes->get('_route'),
            'extras' => ['icon' => 'user-circle-filled'],
        ]);

        $menu->addChild('Passwort ändern', [
            'route' => 'host_change_password',
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
            throw new \InvalidArgumentException('Pass "offer" as opiton');
        }

        /** @var Edition[] $editions */
        $editions = $this->editionRepository->findWithActiveTask('host_editing_stage');

        $menu = $this->factory->createItem('offerActions');

        $menu->addChild('edit', [
            'label' => 'offer.action.edit',
            'route' => 'host_edit_offer',
            'routeParameters' => ['id' => $offer->getId()],
            'display' => $this->isGranted('edit', $offer),
            'extras' => ['icon' => 'pencil-solid'],
        ]);

        $menu->addChild('newVariant', [
            'label' => 'offer.action.newVariant',
            'route' => 'host_edit_offer',
            'routeParameters' => ['source' => null === $offer->getVariantBase() ? $offer->getId() : $offer->getVariantBase()->getId(), 'act' => 'newVariant'],
            'display' => $this->isGranted('create', $offer) && $this->isGranted('edit', $offer),
            'extras' => ['icon' => 'calendar-solid'],
        ]);

        foreach ($editions as $edition) {
            $menu->addChild('copy', [
                'label' => 'offer.action.copy',
                'route' => 'host_edit_offer',
                'routeParameters' => ['source' => $offer->getId(), 'act' => 'copy', 'edition' => $edition->getAlias()],
                'display' => $this->isGranted('view', $offer),
                'extras' => ['icon' => 'duplicate-solid'],
            ]);
        }

        $menu->addChild('delete', [
            'label' => 'offer.action.delete',
            'route' => 'host_view_offer',
            'routeParameters' => ['id' => $offer->getId()],
            'display' => $this->isGranted('delete', $offer),
            'extras' => [
                'method' => 'delete',
                'icon' => 'trash-solid',
            ],
        ]);

        if (!$offer->isCancelled()
            && ((null === $edition = $offer->getEdition()) || (null !== $edition && !$edition->getActiveTasks('show_offers')->isEmpty()))) {
            $menu->addChild('cancel', [
                'label' => 'offer.action.cancel',
                'route' => 'host_view_offer',
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
                'label' => 'offer.action.reactivate',
                'route' => 'host_view_offer',
                'routeParameters' => ['id' => $offer->getId(), 'act' => 'relaunch'],
                'display' => $this->isGranted('reactivate', $offer),
                'extras' => [
                    'method' => 'post',
                    'icon' => 'trash-solid',
                ],
            ]);
        }

        if ($offer->isOnlineApplication()) {
            $menu->addChild('participantList', [
                'label' => 'offer.action.participantList',
                'route' => 'host_participant_list',
                'routeParameters' => ['id' => $offer->getId()],
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
        $request = $this->requestStack->getMasterRequest();
        $menu = $this->factory->createItem('root');

        $user = $this->security->getUser();
        if (!$user instanceof FrontendUser) {
            return $menu;
        }

        $editionNav = $this->factory->createItem('edition');
        $hostNav = $this->factory->createItem('host');

        $editions = $this->editionRepository->findBy([], ['id' => 'DESC'], 5);
        $defaultEdition = $this->editionRepository->findDefaultForHost();
        foreach ($editions as $edition) {
            $editionNav->addChild($edition->getAlias(), [
                'label' => $edition->getName(),
                'route' => 'host_offer_list',
                'routeParameters' => ['edition' => $edition->getAlias()] + $request->query->all(),
                'current' => $request->query->has('edition')
                    ? $edition->getAlias() === $request->query->get('edition')
                    : $edition->getId() === $defaultEdition->getId(),
            ]);
        }

        foreach ($this->hostRepository->findByMemberId((int) $user->id) as $host) {
            $hostNav->addChild($host->getAlias(), [
                'label' => $host->getName(),
                'route' => 'host_offer_list',
                'routeParameters' => ['host' => $host->getAlias()] + $request->query->all(),
                'current' => $request->query->has('host') ? $host->getAlias() === $request->query->get('host') : true,
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
            'label' => 'offer.action.participantList',
            'route' => 'host_participant_list',
            'routeParameters' => ['id' => $offer->getId(), '_suffix' => '.pdf'],
            'extras' => [
                'icon' => 'document-download-solid',
            ],
        ]);

        if (!$offer->isCancelled()
            && ((null === $edition = $offer->getEdition()) || (null !== $edition && !$edition->getActiveTasks('show_offers')->isEmpty()))) {
            $menu->addChild('cancel', [
                'label' => 'offer.action.cancel',
                'route' => 'host_view_offer',
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
                'label' => 'offer.action.reactivate',
                'route' => 'host_view_offer',
                'routeParameters' => ['id' => $offer->getId(), 'act' => 'relaunch'],
                'display' => $this->isGranted('reactivate', $offer),
                'extras' => [
                    'method' => 'post',
                    'icon' => 'trash-solid',
                ],
            ]);
        }

        return $menu;
    }

    private function isGranted(string $attribute, Offer $offer): bool
    {
        return $this->authorizationChecker->isGranted($attribute, $offer);
    }
}
