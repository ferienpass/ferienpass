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

namespace Ferienpass\UX\HostPortal\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;

final class FerienpassUXHostPortalExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
//        $container
//            ->setDefinition('form.dropzone', new Definition(DropzoneType::class))
//            ->addTag('form.type')
//            ->setPublic(false)
//        ;
    }

    public function prepend(ContainerBuilder $container): void
    {
        // Register the custom form types theme if TwigBundle is available
        $bundles = $container->getParameter('kernel.bundles');

        if (!isset($bundles['TwigBundle'])) {
            return;
        }

        $config = ['form_themes' => [
            '@FerienpassUXHostPortal/form_types.html.twig',
        ]];

        $container->prependExtensionConfig('twig', $config);
    }
}
