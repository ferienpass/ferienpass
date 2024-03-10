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
use Ferienpass\CmsBundle\Controller\AbstractController;
use Ferienpass\CmsBundle\Fragment\FragmentReference;
use Ferienpass\CoreBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\UriSigner;

#[AsPage('registration_activate', path: '{id}', requirements: ['id' => '\d+'], contentComposition: false)]
class RegistrationActivatePage extends AbstractController
{
    public function __construct(private readonly UriSigner $uriSigner)
    {
    }

    public function __invoke(int $id, Request $request): Response
    {
        if (!$this->uriSigner->checkRequest($request)) {
            throw new PageNotFoundException();
        }

        /** @var User $user */
        if (null !== $user = $this->getUser()) {
            if ($user->getId() === $id) {
                return $this->redirectToRoute('registration_welcome');
            }
            throw new PageNotFoundException();
        }

        return $this->createPageBuilder($request->attributes->get('pageModel'))
            ->addFragment('main', new FragmentReference('ferienpass.fragment.registration_activate', ['id' => $id]))
            ->getResponse()
        ;
    }
}
