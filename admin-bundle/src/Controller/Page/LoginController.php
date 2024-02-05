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

namespace Ferienpass\AdminBundle\Controller\Page;

use Ferienpass\AdminBundle\Form\UserLoginType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

#[Route('/login', name: 'admin_login')]
final class LoginController extends AbstractController
{
    public function __construct(private readonly UriSigner $uriSigner)
    {
    }

    public function __invoke(AuthenticationUtils $authenticationUtils, Request $request): Response
    {
        $form = $this->createForm(UserLoginType::class, null, ['target_path' => base64_encode($this->targetPath($request))]);

        return $this->render('@FerienpassAdmin/page/login/index.html.twig', [
            'error' => $authenticationUtils->getLastAuthenticationError(),
            'login' => $form->createView(),
        ]);
    }

    private function targetPath(Request $request): string
    {
        // If the form was submitted and the credentials were wrong, take the target
        // path from the submitted data as otherwise it would take the current page
        if ($request->isMethod('POST')) {
            return base64_decode((string) $request->request->get('_target_path'), true);
        }

        if ($request->query->has('redirect')) {
            if ($this->uriSigner->checkRequest($request)) {
                return (string) $request->query->get('redirect');
            }
        }

        return $request->getSchemeAndHttpHost().$request->getRequestUri();
    }
}
