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

namespace Ferienpass\CoreBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('ferienpass');
        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('participant_list')
                    ->children()
                        ->scalarNode('docx_template')
                            ->end()
                    ->end()
                ->end()
                ->scalarNode('logos_dir')
                    ->defaultValue('%kernel.project_dir%/files/logos')
                ->end()
                ->scalarNode('images_dir')
                    ->defaultValue('%kernel.project_dir%/files/bilder')
                ->end()
                ->append($this->addExportNode())
            ->end()
        ;

        return $treeBuilder;
    }

    private function addExportNode()
    {
        $node = (new TreeBuilder('export'))->getRootNode()
            ->children()
                ->arrayNode('pdf')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('template')
                            ->end()
                            ->arrayNode('mpdf_config')
                                ->variablePrototype()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('xml')
                    ->scalarPrototype()->end()
                ->end()
            ->end()
        ;

        return $node;
    }
}
