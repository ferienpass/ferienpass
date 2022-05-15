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

namespace Ferienpass\BackupSftpBundle\DependencyInjection;

use Contao\CoreBundle\DependencyInjection\Filesystem\ConfigureFilesystemInterface;
use Contao\CoreBundle\DependencyInjection\Filesystem\FilesystemConfiguration;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

final class FerienpassBackupSftpExtension extends Extension implements ConfigureFilesystemInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
    }

    public function configureFilesystem(FilesystemConfiguration $config): void
    {
        $config
            ->mountAdapter('sftp', [
                'host' => '%env(DB_STORAGE_HOST)%',
                'port' => 22,
                'username' => '%env(DB_STORAGE_USERNAME)%',
                'password' => '%env(DB_STORAGE_PASSWORD)%',
                'root' => '/db',
                'timeout' => 10,
            ], 'backups', 'backups')
        ;
    }
}
