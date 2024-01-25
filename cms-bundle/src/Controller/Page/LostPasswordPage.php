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
use Contao\CoreBundle\Exception\PageNotFoundException;
use Ferienpass\CmsBundle\Controller\Frontend\AbstractController;
use Ferienpass\CmsBundle\Fragment\FragmentReference;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsPage('lost_password', path: '{method?request}', contentComposition: false)]
class LostPasswordPage extends AbstractController
{
    public function __invoke(string $method, Request $request): Response
    {
        $this->initializeContaoFramework();

        $pageBuilder = $this->createPageBuilder($request->attributes->get('pageModel'));

        switch ($method) {
            case 'request':
                $pageBuilder->addFragment('main', new FragmentReference('ferienpass.fragment.lost_password'));
                break;
            case 'requested':
                $pageBuilder->addFragment('main', new FragmentReference('ferienpass.fragment.lost_password_requested'));
                break;
            case 'reset':
                $pageBuilder->addFragment('main', new FragmentReference('ferienpass.fragment.lost_password_reset'));
                break;
            default:
                throw new PageNotFoundException();
        }

        return $pageBuilder->getResponse();
    }
}
