<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Ferienpass\AdminBundle\Form\Filter\AccountsFilter;
use Ferienpass\AdminBundle\Form\Filter\HostsFilter;
use Ferienpass\AdminBundle\Form\Filter\OffersFilter;
use Ferienpass\AdminBundle\Form\Filter\PaymentsFilter;
use Ferienpass\AdminBundle\Menu\ActionsBuilder;
use Ferienpass\AdminBundle\Menu\MenuBuilder;
use Knp\Menu\Twig\Helper;
use Twig\Extension\StringLoaderExtension;

return function(ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
        ->autoconfigure()
        ->autowire()
    ;

    $services
        ->load('Ferienpass\\AdminBundle\\', '../src/')
        ->exclude('../src/{DependencyInjection,Entity}')
    ;

    // Tags by directory
    $services
        ->load('Ferienpass\\AdminBundle\\Controller\\Dashboard\\', '../src/Controller/Dashboard/')
        ->tag('ferienpass_admin.dashboard_widget')
    ;

    $services
        ->load('Ferienpass\\AdminBundle\\Controller\\Statistics\\', '../src/Controller/Statistics/')
        ->tag('ferienpass_admin.stats_widget')
    ;

    // Tags autoconfigure
    $services
        ->get(OffersFilter::class)
        ->tag('ferienpass_admin.filter')
    ;
    $services
        ->get(PaymentsFilter::class)
        ->tag('ferienpass_admin.filter')
    ;
    $services
        ->get(AccountsFilter::class)
        ->tag('ferienpass_admin.filter')
    ;
    $services
        ->get(HostsFilter::class)
        ->tag('ferienpass_admin.filter')
    ;

    // Aliases for autowiring
    $services->alias(Helper::class, 'knp_menu.helper');

    $services->get(MenuBuilder::class)
        ->tag('knp_menu.menu_builder', ['method' => 'primaryNavigation', 'alias' => 'ferienpass_admin_primary'])
        ->tag('knp_menu.menu_builder', ['method' => 'userNavigation', 'alias' => 'host_user_navigation'])
        ->tag('knp_menu.menu_builder', ['method' => 'offerActions', 'alias' => 'host_offer_actions'])
        ->tag('knp_menu.menu_builder', ['method' => 'offerFilters', 'alias' => 'host_offer_filters'])
        ->tag('knp_menu.menu_builder', ['method' => 'participantListActions', 'alias' => 'host_participant_list_actions'])
    ;

    $services->get(ActionsBuilder::class)
        ->tag('knp_menu.menu_builder', ['method' => 'actions', 'alias' => 'admin_list_actions'])
    ;

    $services->set(StringLoaderExtension::class);

};
