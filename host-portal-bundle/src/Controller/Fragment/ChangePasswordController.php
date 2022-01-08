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

namespace Ferienpass\HostPortalBundle\Controller\Fragment;

use Contao\FrontendUser;
use Ferienpass\CoreBundle\Form\UserChangePasswordType;
use Ferienpass\CoreBundle\Ux\Flash;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Translation\TranslatableMessage;

final class ChangePasswordController extends AbstractFragmentController
{
    private PasswordHasherInterface $passwordHasher;
    private Security $security;

    public function __construct(PasswordHasherInterface $passwordHasher, Security $security)
    {
        $this->passwordHasher = $passwordHasher;
        $this->security = $security;
    }

    public function __invoke(Request $request): Response
    {
        $user = $this->security->getUser();
        if (!$user instanceof FrontendUser) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        $form = $this->createForm(UserChangePasswordType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $user->tstamp = time();
            $user->password = $this->passwordHasher->hash($form->getData()['password'] ?? '');
            $user->save();

            $this->addFlash(...Flash::confirmation()->text(new TranslatableMessage('MSC.newPasswordSet', [], 'contao_default'))->create());
        }

        return $this->render('@FerienpassHostPortal/fragment/change_password.html.twig', ['form' => $form->createView()]);
    }
}
