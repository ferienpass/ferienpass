<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Contao\CoreBundle\Asset\ContaoContext;
use Contao\CoreBundle\Migration\MigrationInterface;
use Contao\CoreBundle\OptIn\OptInInterface;
use Ferienpass\CmsBundle\Controller\Fragment\ChangePasswordController;
use Ferienpass\CmsBundle\Controller\Fragment\CloseAccount;
use Ferienpass\CmsBundle\Controller\Fragment\ParticipantsController;
use Ferienpass\CmsBundle\Controller\Fragment\PersonalDataController;
use Ferienpass\CmsBundle\Menu\MenuBuilder;
use Ferienpass\CmsBundle\Page\PageBuilderFactory;
use Symfony\Component\HttpKernel\Fragment\FragmentHandler;
use Symfony\Component\Security\Http\Logout\LogoutUrlGenerator;

return function(ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
        ->autoconfigure()
        ->autowire()
    ;

    $services
        ->load('Ferienpass\\CmsBundle\\', '../src/')
        ->exclude('../src/{DependencyInjection,Entity}')
    ;

    // Tags by interface
    $services
        ->instanceof(MigrationInterface::class)
        ->tag('contao.migration')
    ;

    // Tags by directory
    $services
        ->load('Ferienpass\\CmsBundle\\Controller\\Fragment\\', '../src/Controller/Fragment/')
        ->tag('ferienpass.fragment')
    ;

    // Aliases for autowiring
    $services->alias(ContaoContext::class, 'contao.assets.files_context');
    $services->alias(OptInInterface::class, 'contao.opt_in');
    $services->alias(LogoutUrlGenerator::class, 'security.logout_url_generator');
    $services->alias(FragmentHandler::class, 'contao.fragment.handler');

    // Tagged user account fragments
    $services->get(ParticipantsController::class)
        ->tag('ferienpass.user_account', ['key' => 'participants', 'alias' => 'teilnehmer', 'icon' => 'user-group'])
    ;

    $services->get(PersonalDataController::class)
        ->tag('ferienpass.user_account', ['key' => 'personal_data', 'alias' => 'persönliche-daten', 'icon' => 'user-circle'])
    ;

    $services->get(ChangePasswordController::class)
        ->tag('ferienpass.user_account', ['key' => 'change_password', 'alias' => 'passwort-ändern', 'icon' => 'lock-closed'])
    ;

    $services->get(CloseAccount::class)
        ->tag('ferienpass.user_account', ['key' => 'close_account', 'alias' => 'account-löschen', 'icon' => 'trash'])
    ;

    // Tagged menu builders
    $services->set(MenuBuilder::class)
        ->tag('knp_menu.menu_builder', ['method' => 'userNavigation', 'alias' => 'user_navigation'])
        ->tag('knp_menu.menu_builder', ['method' => 'userAccountNavigation', 'alias' => 'user_account_navigation'])
    ;

    // Public services
    $services->set(PageBuilderFactory::class)
        ->public()
    ;
};
