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

use Doctrine\ORM\EntityManagerInterface;
use Ferienpass\CoreBundle\Entity\User;
use Ferienpass\CoreBundle\Form\UserChangePasswordType;
use Ferienpass\CoreBundle\Session\Flash;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/passwort-aendern', name: 'admin_password')]
final class ChangePasswordController extends AbstractController
{
    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function __invoke(EntityManagerInterface $em, Request $request, Flash $flash): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        $form = $this->createForm(UserChangePasswordType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $hashedPassword = $this->passwordHasher->hashPassword($user, $form->getData()['password'] ?? '');
            $user->setPassword($hashedPassword);
            $user->setModifiedAt();

            $em->flush();

            $flash->addConfirmation(text: new TranslatableMessage('MSC.newPasswordSet', [], 'contao_default'));
        }

        return $this->render('@FerienpassAdmin/page/user/change_password.html.twig', [
            'headline' => 'user.password.title',
            'form' => $form->createView(),
        ]);
    }
}
