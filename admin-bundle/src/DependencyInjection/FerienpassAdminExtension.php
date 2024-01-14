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

namespace Ferienpass\AdminBundle\DependencyInjection;

use DoctrineExtensions\Query\Mysql\DateFormat;
use DoctrineExtensions\Query\Mysql\TimestampDiff;
use Ferienpass\AdminBundle\State\PrivacyConsent;
use Scienta\DoctrineJsonFunctions\Query\AST\Functions\Mysql\JsonSearch;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

final class FerienpassAdminExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        // $container->getParameter('router.request_context.host')
        $config = $this->processConfiguration(new Configuration(), $configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../../config'));
        $loader->load('components.xml');
        $loader->load('dashboard.xml');
        $loader->load('fragments.xml');
        $loader->load('pages.xml');
        $loader->load('services.xml');
        $loader->load('statistics.xml');

        $definition = $container->getDefinition(PrivacyConsent::class);
        $definition->setArgument(2, $config['privacy_consent'] ?? '');
    }

    public function prepend(ContainerBuilder $container): void
    {
        $this->prependTwigBundle($container);
        $this->prependDoctrineBundle($container);

        $container->prependExtensionConfig('twig_component', [
            'defaults' => [
                'Ferienpass\AdminBundle\Components\\' => [
                    'template_directory' => '@FerienpassAdmin/components',
                    'name_prefix' => 'Admin',
                ],
            ],
        ]);

        if ($this->isAssetMapperAvailable($container)) {
            $container->prependExtensionConfig('framework', [
                'asset_mapper' => [
                    'paths' => [
                        __DIR__.'/../../assets/dist' => '@ferienpass/ux-admin',
                    ],
                ],
            ]);
        }
    }

    private function prependTwigBundle(ContainerBuilder $container): void
    {
        $config = ['form_themes' => [
            '@FerienpassAdmin/form/custom_types.html.twig',
            '@FerienpassAdmin/form/form_types.html.twig',
        ]];

        $container->prependExtensionConfig('twig', $config);
    }

    private function prependDoctrineBundle(ContainerBuilder $container): void
    {
        $container->prependExtensionConfig('doctrine', [
            'orm' => [
                'dql' => [
                    'string_functions' => [
                        'DATE_FORMAT' => DateFormat::class,
                        'TIMESTAMPDIFF' => TimestampDiff::class,
                        'JSON_SEARCH' => JsonSearch::class,
                    ],
                ],
            ],
        ]);
    }

    private function isAssetMapperAvailable(ContainerBuilder $container): bool
    {
        if (!interface_exists(AssetMapperInterface::class)) {
            return false;
        }

        // check that FrameworkBundle 6.3 or higher is installed
        $bundlesMetadata = $container->getParameter('kernel.bundles_metadata');
        if (!isset($bundlesMetadata['FrameworkBundle'])) {
            return false;
        }

        return is_file($bundlesMetadata['FrameworkBundle']['path'].'/Resources/config/asset_mapper.php');
    }
}
