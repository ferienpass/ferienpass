<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Ferienpass\AdminBundle\Form\Filter\AccountsFilter;
use Ferienpass\AdminBundle\Form\Filter\HostsFilter;
use Ferienpass\AdminBundle\Form\Filter\Offer\EditionFilter;
use Ferienpass\AdminBundle\Form\Filter\Offer\HostFilter;
use Ferienpass\AdminBundle\Form\Filter\Offer\OnlineApplicationFilter;
use Ferienpass\AdminBundle\Form\Filter\Offer\PublishedFilter;
use Ferienpass\AdminBundle\Form\Filter\Offer\RequiresAgreementLetterFilter;
use Ferienpass\AdminBundle\Form\Filter\Offer\RequiresApplicationFilter;
use Ferienpass\AdminBundle\Form\Filter\Offer\StatusFilter;
use Ferienpass\AdminBundle\Form\Filter\OffersFilter;
use Ferienpass\AdminBundle\Form\Filter\Payment\UserFilter;
use Ferienpass\AdminBundle\Form\Filter\PaymentsFilter;
use Ferienpass\AdminBundle\Menu\ActionsBuilder;
use Ferienpass\AdminBundle\Menu\MenuBuilder;
use Ferienpass\AdminBundle\Service\FileUploader;
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

    $services
        ->get(EditionFilter::class)
        ->tag('ferienpass_admin.filter.offer', ['key' => 'edition', 'priority' => 90])
    ;
    $services
        ->get(HostFilter::class)
        ->tag('ferienpass_admin.filter.offer', ['key' => 'host', 'priority' => 90])
    ;
    $services
        ->get(OnlineApplicationFilter::class)
        ->tag('ferienpass_admin.filter.offer', ['key' => 'onlineApplication', 'priority' => 70])
    ;
    $services
        ->get(RequiresApplicationFilter::class)
        ->tag('ferienpass_admin.filter.offer', ['key' => 'requiresApplication', 'priority' => 80])
    ;
    $services
        ->get(RequiresAgreementLetterFilter::class)
        ->tag('ferienpass_admin.filter.offer', ['key' => 'requiresAgreementLetter', 'priority' => 70])
    ;
    $services
        ->get(StatusFilter::class)
        ->tag('ferienpass_admin.filter.offer', ['key' => 'status', 'priority' => 60])
    ;

    $services
        ->get(\Ferienpass\AdminBundle\Form\Filter\Payment\StatusFilter::class)
        ->tag('ferienpass_admin.filter.payment', ['key' => 'status'])
    ;
    $services
        ->get(UserFilter::class)
        ->tag('ferienpass_admin.filter.payment', ['key' => 'user'])
    ;

    // Aliases for autowiring
    $services->alias(Helper::class, 'knp_menu.helper');

    $services->get(MenuBuilder::class)
        ->tag('knp_menu.menu_builder', ['method' => 'primaryNavigation', 'alias' => 'admin_primary'])
        ->tag('knp_menu.menu_builder', ['method' => 'userNavigation', 'alias' => 'host_user_navigation'])
        ->tag('knp_menu.menu_builder', ['method' => 'offerActions', 'alias' => 'host_offer_actions'])
        ->tag('knp_menu.menu_builder', ['method' => 'offerFilters', 'alias' => 'host_offer_filters'])
        ->tag('knp_menu.menu_builder', ['method' => 'participantListActions', 'alias' => 'host_participant_list_actions'])
    ;

    $services->get(ActionsBuilder::class)
        ->tag('knp_menu.menu_builder', ['method' => 'actions', 'alias' => 'admin_list_actions'])
    ;

    $services->set(FileUploader::class)->abstract();
    $services->set('ferienpass.file_uploader.offer_media')
        ->parent(FileUploader::class)
        ->arg(0, '%kernel.project_dir%')
        ->arg(1, '%contao.upload_path%/Angebote')
    ;
    $services->set('ferienpass.file_uploader.logos')
        ->parent(FileUploader::class)
        ->arg(0, '%kernel.project_dir%')
        ->arg(1, '%contao.upload_path%/Logos')
    ;
    $services->set('ferienpass.file_uploader.agreement_letters')
        ->parent(FileUploader::class)
        ->arg(0, '%kernel.project_dir%')
        ->arg(1, '%contao.upload_path%/Einverständniserklärungen')
    ;

    $services->set(StringLoaderExtension::class);
};
