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

use Contao\CoreBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Ferienpass\CoreBundle\Entity\User;
use Ferienpass\CoreBundle\Message\AccountDelete;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class CloseAccount extends AbstractController
{
    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher, private readonly MessageBusInterface $messageBus, private readonly Security $security, private readonly EntityManagerInterface $entityManager)
    {
    }

    public function __invoke(Request $request, Session $session): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        if ($request->isMethod('POST') && 'extend_account' === $request->request->get('FORM_SUBMIT')) {
            $user->setDontDeleteBefore(new \DateTimeImmutable('+15 months'));

            $this->entityManager->flush();

            return $this->redirect($request->getUri());
        }

        if ($request->isMethod('POST') && 'close_account' === $request->request->get('FORM_SUBMIT')) {
            if ($this->passwordHasher->isPasswordValid($user, $request->request->get('password'))) {
                $this->messageBus->dispatch(new AccountDelete($user->getId()));

                $this->security->logout();

                return $this->redirectToRoute('account_deleted');
            }

            return new JsonResponse(['error' => 'The password is not correct.'], Response::HTTP_BAD_REQUEST);
        }

        return $this->render('@FerienpassCms/fragment/user_account/close_account.html.twig');
    }
}
