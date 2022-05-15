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

namespace Ferienpass\HostPortalBundle\Controller\Page;

use Ferienpass\HostPortalBundle\Fragment\FragmentReference;
use Ferienpass\HostPortalBundle\Page\PageBuilder;

final class HostRegistrationPage extends AbstractContentPage
{
    protected function modifyPage(PageBuilder $pageBuilder): void
    {
        $pageBuilder
            ->addFragment('main', new FragmentReference('ferienpass.fragment.host.registration'))
        ;
    }
}
