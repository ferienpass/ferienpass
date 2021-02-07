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

use Contao\CoreBundle\ServiceAnnotation\Page;
use Ferienpass\HostPortalBundle\Controller\AbstractController;
use Ferienpass\HostPortalBundle\Fragment\FragmentReference;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Page(type="host_edit_offer", path="{id}", requirements={"id"="\d+"}, defaults={"id"=0}, contentComposition=false)
 */
final class EditOfferPage extends AbstractController
{
    public function __invoke(int $id, Request $request): Response
    {
        $this->checkToken();

        // Access check in fragment

        return $this->createPageBuilder($request->get('pageModel'))
            ->addFragment('main', new FragmentReference('ferienpass.fragment.host.offer_editor', ['id' => $id]))
            ->getResponse()
            ;
    }
}
