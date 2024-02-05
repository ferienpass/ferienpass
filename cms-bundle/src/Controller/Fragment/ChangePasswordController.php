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

namespace Ferienpass\CmsBundle\Controller\Fragment;

use Contao\CoreBundle\Controller\AbstractFragmentController;
use Doctrine\ORM\EntityManagerInterface;
use Ferienpass\CmsBundle\Form\UserChangePasswordType;
use Ferienpass\CoreBundle\Entity\User;
use Ferienpass\CoreBundle\Ux\Flash;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Translation\TranslatableMessage;

final class ChangePasswordController extends AbstractFragmentController
{
    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher, private readonly EntityManagerInterface $entityManager)
    {
    }

    public function __invoke(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        $form = $this->createForm(UserChangePasswordType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $hashedPassword = $this->passwordHasher->hashPassword($user, $form->get('plainPassword')->getData());
            $user->setPassword($hashedPassword);
            $user->setModifiedAt();

            $this->entityManager->flush();

            $this->addFlash(...Flash::confirmation()->text(new TranslatableMessage('MSC.newPasswordSet', [], 'contao_default'))->create());
        }

        return $this->render('@FerienpassCms/fragment/user_account/change_password.html.twig', ['form' => $form]);
    }
}
