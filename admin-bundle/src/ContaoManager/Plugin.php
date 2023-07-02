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

namespace Ferienpass\AdminBundle\ContaoManager;

use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Config\ConfigPluginInterface;
use Contao\ManagerPlugin\Config\ContainerBuilder;
use Contao\ManagerPlugin\Config\ExtensionPluginInterface;
use Contao\ManagerPlugin\Routing\RoutingPluginInterface;
use Ferienpass\AdminBundle\FerienpassAdminBundle;
use Ferienpass\CoreBundle\FerienpassCoreBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class Plugin implements BundlePluginInterface, RoutingPluginInterface, ConfigPluginInterface, ExtensionPluginInterface
{
    public function getBundles(ParserInterface $parser): array
    {
        return [
            BundleConfig::create(FerienpassAdminBundle::class)
                ->setLoadAfter([FerienpassCoreBundle::class]),
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader, array $managerConfig): void
    {
        // $loader->load(__DIR__.'/../../config/packages/security.xml');
    }

    public function getRouteCollection(LoaderResolverInterface $resolver, KernelInterface $kernel)
    {
        return $resolver
            ->resolve(__DIR__.'/../../config/routes.xml')
            ->load(__DIR__.'/../../config/routes.xml')
        ;
    }

    public function getExtensionConfig($extensionName, array $extensionConfigs, ContainerBuilder $container)
    {
        if ('security' === $extensionName) {
            foreach ($extensionConfigs as &$extensionConfig) {
                $extensionConfig['firewalls']['ferienpass_admin'] = [
                    'lazy' => true,
                    'provider' => 'contao.security.frontend_user_provider',
                    'user_checker' => 'contao.security.user_checker',
                    'form_login' => [
                        'login_path' => 'admin_login',
                        'check_path' => 'admin_login',
                        'enable_csrf' => true,
                    ],
                    'logout' => [
                        'path' => 'admin_logout',
                    ],
                    'remember_me' => [
                        'secret' => '%kernel.secret%',
                        'always_remember_me' => true,
                    ],
                ];

                array_unshift($extensionConfig['access_control'], [
                    'path' => '^/admin/(login|passwort-vergessen|registrierung)$',
                    'roles' => 'PUBLIC_ACCESS',
                ], [
                    'path' => '^/admin',
                    'roles' => 'ROLE_HOST',
                ]);

                break;
            }
        }

        if ('framework' === $extensionName) {
            foreach ($extensionConfigs as &$extensionConfig) {
                $extensionConfig['assets']['packages']['ferienpass_admin'] = ['json_manifest_path' => '%kernel.project_dir%/web/themes/admin/manifest.json'];

                break;
            }
        }

        if ('webpack_encore' === $extensionName) {
            foreach ($extensionConfigs as &$extensionConfig) {
                $extensionConfig['builds']['ferienpass_admin'] = '%kernel.project_dir%/web/themes/admin';
                $extensionConfig['output_path'] = $extensionConfigs['output_path'] ?? $extensionConfig['builds']['ferienpass_admin'];

                break;
            }
        }

        return $extensionConfigs;
    }
}
