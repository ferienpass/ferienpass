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

namespace Ferienpass\CoreBundle\Menu;

use Contao\PageModel;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Logout\LogoutUrlGenerator;

class MenuBuilder
{
    public function __construct(private FactoryInterface $factory, private LogoutUrlGenerator $logoutUrlGenerator, private RequestStack $requestStack)
    {
    }

    public function userNavigation(): ItemInterface
    {
        $menu = $this->factory->createItem('root');

        $menu->addChild('Teilnehmer:innen', [
            'route' => 'user_account',
            'routeParameters' => ['fragment' => 'participants'],
            'current' => $this->isCurrent('user_account', 'participants'),
            'extras' => ['icon' => 'user-group-solid'],
        ]);

        $menu->addChild('Nutzer-Account', [
            'route' => 'user_account',
            'routeParameters' => ['fragment' => 'personal_data'],
            'current' => $this->isCurrent('user_account', 'personal_data'),
            'extras' => ['icon' => 'lock-closed-solid'],
        ]);

        $menu->addChild('Abmelden', [
            'uri' => $this->logoutUrlGenerator->getLogoutUrl('contao_frontend'),
            'extras' => ['icon' => 'logout-solid'],
        ]);

        return $menu;
    }

    public function userAccountNavigation(): ItemInterface
    {
        $menu = $this->factory->createItem('root');

        $menu->addChild('Teilnehmer:innen', [
            'route' => 'user_account',
            'routeParameters' => ['fragment' => 'participants'],
            'current' => $this->isCurrent('user_account', 'participants'),
            'extras' => ['icon' => 'user-group'],
        ]);

        $menu->addChild('Persönliche Daten', [
            'route' => 'user_account',
            'routeParameters' => ['fragment' => 'personal_data'],
            'current' => $this->isCurrent('user_account', 'personal_data'),
            'extras' => ['icon' => 'user-circle'],
        ]);

        $menu->addChild('Passwort ändern', [
            'route' => 'user_account',
            'routeParameters' => ['fragment' => 'change_password'],
            'current' => $this->isCurrent('user_account', 'change_password'),
            'extras' => ['icon' => 'lock-closed'],
        ]);

        $menu->addChild('Account löschen', [
            'route' => 'user_account',
            'routeParameters' => ['fragment' => 'close_account'],
            'current' => $this->isCurrent('user_account', 'close_account'),
            'extras' => ['icon' => 'trash'],
        ]);

        return $menu;
    }

    private function isCurrent(string $type, string $fragment = null): bool
    {
        $request = $this->requestStack->getMainRequest();
        if (null === $request) {
            return false;
        }

        $pageModel = $request->attributes->get('pageModel');
        if (!$pageModel instanceof PageModel) {
            return false;
        }

        if (null === $fragment) {
            return $type === $pageModel->type;
        }

        return $type === $pageModel->type && $fragment === $request->attributes->get('fragment');
    }
}
