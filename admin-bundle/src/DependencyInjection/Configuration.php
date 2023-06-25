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

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('ferienpass_host_portal');
        $treeBuilder->getRootNode()
            ->children()
            ->scalarNode('privacy_consent')
                ->beforeNormalization()
                ->ifString()
            ->then(static fn ($v) => file_get_contents($v))
            ->end()
        ;

        return $treeBuilder;
    }
}
