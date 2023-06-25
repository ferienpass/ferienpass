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

use Contao\FrontendUser;
use Ferienpass\CoreBundle\Form\UserChangePasswordType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/passwort-aendern', name: 'admin_password')]
final class ChangePasswordController extends AbstractController
{
    public function __construct(private PasswordHasherInterface $passwordHasher)
    {
    }

    public function __invoke(Request $request, FormFactoryInterface $formFactory, \Ferienpass\CoreBundle\Session\Flash $flash): Response
    {
        $user = $this->getUser();
        if (!$user instanceof FrontendUser) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        $form = $formFactory->create(UserChangePasswordType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $user->tstamp = time();
            $user->password = $this->passwordHasher->hash($form->getData()['password'] ?? '');
            $user->save();

            $flash->addConfirmation(text: new TranslatableMessage('MSC.newPasswordSet', [], 'contao_default'));
        }

        return $this->render('@FerienpassAdmin/page/user/change_password.html.twig', [
            'headline' => 'user.password.title',
            'form' => $form,
        ]);
    }
}
