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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class PersonalDataPage extends AbstractContentPage
{
    public function __invoke(Request $request): Response
    {
        $this->checkToken();

        return $this
            ->createPageBuilder($request->attributes->get('pageModel'))
            ->addFragment('main', new FragmentReference('ferienpass.fragment.host.personal_data'))
            ->getResponse()
        ;
    }
}
