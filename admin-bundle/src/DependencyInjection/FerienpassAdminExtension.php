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

use Ferienpass\AdminBundle\State\PrivacyConsent;
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
        // Register the custom form types theme if TwigBundle is available
        $bundles = $container->getParameter('kernel.bundles');

        if (!isset($bundles['TwigBundle'])) {
            return;
        }

        $config = ['form_themes' => [
            '@FerienpassAdmin/form/custom_types.html.twig',
        ]];

        $container->prependExtensionConfig('twig', $config);
    }
}
