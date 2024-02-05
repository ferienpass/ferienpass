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
use Ferienpass\AdminBundle\Form\ChangePasswordFormType;
use Ferienpass\AdminBundle\Form\ResetPasswordRequestFormType;
use Ferienpass\AdminBundle\Message\HostLostPassword;
use Ferienpass\CoreBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

#[Route('/passwort-vergessen')]
final class LostPasswordController extends AbstractController
{
    use ResetPasswordControllerTrait;

    public function __construct(private ResetPasswordHelperInterface $resetPasswordHelper, private EntityManagerInterface $entityManager)
    {
    }

    #[Route('', name: 'admin_lost_password')]
    public function index(Request $request, MessageBusInterface $messageBus): Response
    {
        if ($this->isGranted('ROLE_USER')) {
            return $this->redirectToRoute('admin_index');
        }

        $form = $this->createForm(ResetPasswordRequestFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $messageBus->dispatch(new HostLostPassword($form->get('email')->getData()));

            return $this->redirectToRoute('admin_lost_password_check_email');
        }

        return $this->render('@FerienpassAdmin/page/reset_password/request.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/angefragt', name: 'admin_lost_password_check_email')]
    public function checkEmail(): Response
    {
        if ($this->isGranted('ROLE_USER')) {
            return $this->redirectToRoute('admin_index');
        }

        // Generate a fake token if the user does not exist or someone hit this page directly.
        // This prevents exposing whether or not a user was found with the given email address or not
        if (null === ($resetToken = $this->getTokenObjectFromSession())) {
            $resetToken = $this->resetPasswordHelper->generateFakeResetToken();
        }

        return $this->render('@FerienpassAdmin/page/reset_password/check_email.html.twig', [
            'resetToken' => $resetToken,
        ]);
    }

    #[Route('/reset/{token}', name: 'admin_lost_password_reset')]
    public function reset(Request $request, UserPasswordHasherInterface $passwordHasher, TranslatorInterface $translator, string $token = null): Response
    {
        if ($this->isGranted('ROLE_USER')) {
            return $this->redirectToRoute('admin_index');
        }

        if ($token) {
            // We store the token in session and remove it from the URL, to avoid the URL being
            // loaded in a browser and potentially leaking the token to 3rd party JavaScript.
            $this->storeTokenInSession($token);

            return $this->redirectToRoute('admin_lost_password_reset');
        }

        $token = $this->getTokenFromSession();

        if (null === $token) {
            throw $this->createNotFoundException('No reset password token found in the URL or in the session.');
        }

        try {
            /** @var User $user */
            $user = $this->resetPasswordHelper->validateTokenAndFetchUser($token);
        } catch (ResetPasswordExceptionInterface $e) {
            $this->addFlash('reset_password_error', sprintf(
                '%s - %s',
                $translator->trans(ResetPasswordExceptionInterface::MESSAGE_PROBLEM_VALIDATE, [], 'ResetPasswordBundle'),
                $translator->trans($e->getReason(), [], 'ResetPasswordBundle')
            ));

            return $this->redirectToRoute('admin_lost_password');
        }

        // The token is valid; allow the user to change their password.
        $form = $this->createForm(ChangePasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // A password reset token should be used only once, remove it.
            $this->resetPasswordHelper->removeResetRequest($token);

            $encodedPassword = $passwordHasher->hashPassword($user, $form->get('plainPassword')->getData());

            $user->setPassword($encodedPassword);
            $user->setModifiedAt();
            $this->entityManager->flush();

            $this->cleanSessionAfterReset();

            return $this->redirectToRoute('admin_index');
        }

        return $this->render('@FerienpassAdmin/page/reset_password/reset.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
