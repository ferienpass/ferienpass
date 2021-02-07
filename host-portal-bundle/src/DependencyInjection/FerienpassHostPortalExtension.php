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

namespace Ferienpass\HostPortalBundle\DependencyInjection;

use Ferienpass\HostPortalBundle\State\PrivacyConsent;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

final class FerienpassHostPortalExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../../config'));
        $loader->load('services.xml');
        $loader->load('pages.xml');
        $loader->load('fragments.xml');

        $definition = $container->getDefinition(PrivacyConsent::class);
        $definition->setArgument(2, $config['privacy_consent'] ?? '');
    }

    public function prepend(ContainerBuilder $container): void
    {
        // Register the custom form types theme if TwigBundle is available
        $bundles = $container->getParameter('kernel.bundles');

        if (!isset($bundles['TwigBundle'])) {
            return;
        }

        $config = ['form_themes' => [
            '@FerienpassHostPortal/form/custom_types.html.twig',
        ]];

        $container->prependExtensionConfig('twig', $config);
    }
}
