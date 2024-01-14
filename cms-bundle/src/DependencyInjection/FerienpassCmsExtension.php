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

namespace Ferienpass\CmsBundle\DependencyInjection;

use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

final class FerienpassCmsExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../config'));
        $loader->load('services.php');

        //        $definition = $container->getDefinition(PrivacyConsent::class);
        //        $definition->setArgument(2, $config['privacy_consent'] ?? '');
    }

    public function prepend(ContainerBuilder $container): void
    {
        $this->prependTwigBundle($container);
        $this->prependDoctrineBundle($container);

                if ($this->isAssetMapperAvailable($container)) {
                    $container->prependExtensionConfig('framework', [
                        'asset_mapper' => [
                            'paths' => [
                                __DIR__.'/../../assets/dist' => '@ferienpass/ux-cms',
                            ],
                        ],
                    ]);
                }
    }

    private function prependTwigBundle(ContainerBuilder $container): void
    {
        //        $config = ['form_themes' => [
        //            '@FerienpassAdmin/form/custom_types.html.twig',
        //            '@FerienpassAdmin/form/form_types.html.twig',
        //        ]];
        //
        //        $container->prependExtensionConfig('twig', $config);
    }

    private function prependDoctrineBundle(ContainerBuilder $container): void
    {
        //        $container->prependExtensionConfig('doctrine', [
        //            'orm' => [
        //                'dql' => [
        //                    'string_functions' => [
        //                        'DATE_FORMAT' => DateFormat::class,
        //                        'TIMESTAMPDIFF' => TimestampDiff::class,
        //                        'JSON_SEARCH' => JsonSearch::class,
        //                    ],
        //                ],
        //            ],
        //        ]);
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
