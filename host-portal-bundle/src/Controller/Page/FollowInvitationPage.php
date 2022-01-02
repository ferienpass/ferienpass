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

use Contao\LayoutModel;
use Ferienpass\HostPortalBundle\Controller\AbstractController;
use Ferienpass\HostPortalBundle\Fragment\FragmentReference;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class FollowInvitationPage extends AbstractController
{
    public function __invoke(Request $request): Response
    {
        $layout = LayoutModel::findBy('alias', 'splash');
        $pageModel = $request->attributes->get('pageModel');
        $pageModel->layout = $layout->id;

        return $this->createPageBuilder($pageModel)
            ->addFragment('main', new FragmentReference('ferienpass.fragment.host.follow_invitation'))
            ->getResponse()
            ;
    }
}
