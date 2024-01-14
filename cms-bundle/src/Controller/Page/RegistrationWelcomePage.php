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
use Ferienpass\CmsBundle\Controller\Frontend\AbstractController;
use Ferienpass\CoreBundle\Fragment\FragmentReference;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsPage('registration_welcome', contentComposition: false)]
class RegistrationWelcomePage extends AbstractController
{
    public function __invoke(Request $request): Response
    {
        $this->initializeContaoFramework();

        $this->checkToken();

        return $this->createPageBuilder($request->attributes->get('pageModel'))
            ->addFragment('main', new FragmentReference('ferienpass.fragment.registration_welcome'))
            ->getResponse()
        ;
    }
}
