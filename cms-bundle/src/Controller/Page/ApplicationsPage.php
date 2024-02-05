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

namespace Ferienpass\CmsBundle\Controller\Page;

use Contao\CoreBundle\DependencyInjection\Attribute\AsPage;
use Ferienpass\CmsBundle\Fragment\FragmentReference;
use Ferienpass\CmsBundle\Page\PageBuilder;

#[AsPage('applications')]
class ApplicationsPage extends AbstractContentPage
{
    protected $protected = true;

    protected function modifyPage(PageBuilder $pageBuilder): void
    {
        $pageBuilder->addFragment('main', new FragmentReference('ferienpass.fragment.application_list'));
    }
}
