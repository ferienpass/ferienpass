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

namespace Ferienpass\BackupSftpBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class FerienpassBackupSftpBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
