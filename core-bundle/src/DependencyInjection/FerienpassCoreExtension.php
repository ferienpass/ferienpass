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

use Ferienpass\CoreBundle\Export\Offer\PrintSheet\PdfExports;
use Ferienpass\CoreBundle\Export\Offer\Xml\XmlExports;
use Ferienpass\CoreBundle\Export\ParticipantList\WordExport;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

final class FerienpassCoreExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../../config'));
        $loader->load('listeners.yml');
        $loader->load('services.yml');
        $loader->load('migrations.yml');

        $xmlLoader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../../config'));
        $xmlLoader->load('fragments.xml');
        $xmlLoader->load('pages.xml');

        // Parameters
        $container->setParameter('ferienpass.logos_dir', $config['logos_dir']);
        $container->setParameter('ferienpass.images_dir', $config['images_dir']);

        // Injection
        if (isset($config['export'])) {
            $pdfConfigs = $container->getDefinition(PdfExports::class);
            foreach ($config['export']['pdf'] as $configName => $pdfConfig) {
                $pdfConfigs->addMethodCall('addConfig', [$configName, $pdfConfig]);
            }

            $xmlExports = $container->getDefinition(XmlExports::class);
            foreach ($config['export']['xml'] as $configName => $template) {
                $xmlExports->addMethodCall('addTemplate', [$configName, $template]);
            }
        }

        $docxParticipantList = $container->getDefinition(WordExport::class);
        $docxParticipantList->setArgument(3, $config['participant_list']['docx_template'] ?? null);
    }

    public function getAlias()
    {
        return 'ferienpass';
    }
}
