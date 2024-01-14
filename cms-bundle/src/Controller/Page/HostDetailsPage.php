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
use Contao\PageModel;
use Ferienpass\CmsBundle\Controller\Frontend\AbstractController;
use Ferienpass\CmsBundle\Fragment\FragmentReference;
use Ferienpass\CoreBundle\Entity\Host;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsPage('host_details', path: '{alias}', contentComposition: false)]
class HostDetailsPage extends AbstractController
{
    public function __invoke(Host $host, Request $request): Response
    {
        $this->initializeContaoFramework();

        $pageModel = $request->attributes->get('pageModel');
        if ($pageModel instanceof PageModel) {
            $pageModel->title = $host->getName();
        }

        return $this->createPageBuilder($request->attributes->get('pageModel'))
            ->addFragment('main', new FragmentReference('ferienpass.fragment.host_details', ['host' => $host]))
            ->getResponse()
        ;
    }
}
