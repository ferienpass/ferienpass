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

namespace Ferienpass\BackupSftpBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Config\ContainerBuilder;
use Contao\ManagerPlugin\Config\ExtensionPluginInterface;
use Ferienpass\BackupSftpBundle\FerienpassBackupSftpBundle;

class Plugin implements BundlePluginInterface, ExtensionPluginInterface
{
    public function getBundles(ParserInterface $parser): array
    {
        return [
            BundleConfig::create(FerienpassBackupSftpBundle::class)
                ->setLoadAfter([ContaoCoreBundle::class]),
        ];
    }

    public function getExtensionConfig($extensionName, array $extensionConfigs, ContainerBuilder $container): array
    {
        if ('contao' === $extensionName) {
            foreach ($extensionConfigs as &$extensionConfig) {
                if (isset($extensionConfig['backup'])) {
                    $extensionConfig['backup']['keep_max'] = 100;
                    $extensionConfig['backup']['keep_intervals'] = [];
                    break;
                }
            }
        }

        return $extensionConfigs;
    }
}
