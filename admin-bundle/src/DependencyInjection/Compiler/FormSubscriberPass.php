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

namespace Ferienpass\AdminBundle\DependencyInjection\Compiler;

use Ferienpass\AdminBundle\Form\FormSubscriberAwareInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class FormSubscriberPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $taggedServices = $container->findTaggedServiceIds('ferienpass_admin.form_subscriber');

        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                if (!$container->has($attributes['type'])) {
                    continue;
                }

                $formType = $container->findDefinition($attributes['type']);
                if (!is_subclass_of($formType->getClass(), FormSubscriberAwareInterface::class)) {
                    continue;
                }

                $formType->addMethodCall('addEventSubscriber', [new Reference($id)]);
            }
        }
    }
}
