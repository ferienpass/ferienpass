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
use Ferienpass\CmsBundle\Form\ChangePasswordFormType;
use Ferienpass\CoreBundle\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

class LostPasswordResetController extends AbstractController
{
    use ResetPasswordControllerTrait;

    public function __construct(private readonly ResetPasswordHelperInterface $resetPasswordHelper, private readonly EntityManagerInterface $entityManager, private readonly UserPasswordHasherInterface $passwordHasher, private readonly TranslatorInterface $translator)
    {
    }

    public function __invoke(Request $request): Response
    {
        $token = $request->query->get('token');
        if ($token) {
            // We store the token in session and remove it from the URL, to avoid the URL being
            // loaded in a browser and potentially leaking the token to 3rd party JavaScript.
            $this->storeTokenInSession($token);

            return $this->redirectToRoute('lost_password', ['method' => 'reset']);
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
                $this->translator->trans(ResetPasswordExceptionInterface::MESSAGE_PROBLEM_VALIDATE, [], 'ResetPasswordBundle'),
                $this->translator->trans($e->getReason(), [], 'ResetPasswordBundle')
            ));

            return $this->redirectToRoute('lost_password');
        }

        // The token is valid; allow the user to change their password.
        $form = $this->createForm(ChangePasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // A password reset token should be used only once, remove it.
            $this->resetPasswordHelper->removeResetRequest($token);

            $encodedPassword = $this->passwordHasher->hashPassword($user, $form->get('plainPassword')->getData());

            $user->setPassword($encodedPassword);
            $this->entityManager->flush();

            $this->cleanSessionAfterReset();

            return $this->redirect('/');
        }

        return $this->render('@FerienpassCms/fragment/reset_password/reset.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
