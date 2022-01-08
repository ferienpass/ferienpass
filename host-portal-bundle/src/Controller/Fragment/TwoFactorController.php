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

use Contao\CoreBundle\Security\TwoFactor\TrustedDeviceManager;
use Contao\FrontendUser;
use Ferienpass\CoreBundle\Form\SimpleType\ContaoRequestTokenType;
use ParagonIE\ConstantTime\Base32;
use Scheb\TwoFactorBundle\Security\Authentication\Exception\InvalidTwoFactorCodeException;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

final class TwoFactorController extends AbstractFragmentController
{
    private Security $security;
    private TrustedDeviceManager $trustedDeviceManager;
    private TranslatorInterface $translator;

    public function __construct(
        Security $security,
        TrustedDeviceManager $trustedDeviceManager,
        TranslatorInterface $translator
    ) {
        $this->security = $security;
        $this->trustedDeviceManager = $trustedDeviceManager;
        $this->translator = $translator;
    }

    public function __invoke(Request $request): Response
    {
        if (!$this->security->isGranted('IS_AUTHENTICATED_FULLY')) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        $template = (object) [];

        $user = $this->security->getUser();
        if (!$user instanceof FrontendUser) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        if (('enable' === $request->get('2fa'))
            && null !== $response = $this->enableTwoFactor($request, $user, $template)) {
            return $response;
        }

        $disableForm = $this->getDisableForm();
        $disableForm->handleRequest($request);
        if ($disableForm->isSubmitted() && $disableForm->isValid() && null !== $response = $this->disableTwoFactor($user, $request)) {
            return $response;
        }

        $clearForm = $this->getClearForm();
        $clearForm->handleRequest($request);
        if ($clearForm->isSubmitted() && $clearForm->isValid()) {
            $this->trustedDeviceManager->clearTrustedDevices($user);
        }

        $template->disable = $disableForm->createView();
        $template->clear = $clearForm->createView();
        $template->trustedDevices = $this->trustedDeviceManager->getTrustedDevices($user);

        return $this->render('@FerienpassHostPortal/fragment/two_factor.html.twig', (array) $template);
    }

    private function enableTwoFactor(Request $request, FrontendUser $user, object $template): ?Response
    {
        // Return if 2FA is enabled already
        if ($user->useTwoFactor) {
            return null;
        }

        $authenticator = $this->container->get('contao.security.two_factor.authenticator');
        $exception = $this->container->get('security.authentication_utils')->getLastAuthenticationError();

        if ($exception instanceof InvalidTwoFactorCodeException) {
            $template->message = $this->trans('ERR.invalidTwoFactor');
        }

        $form = $this->createFormBuilder()
            ->add('verify', TextType::class, [
                'label' => 'MSC.twoFactorVerification',
                'help' => 'MSC.twoFactorVerificationHelp',
                'translation_domain' => 'contao_default',
            ])
            ->add('request_token', ContaoRequestTokenType::class)
            ->add('submit', SubmitType::class, ['label' => $this->trans('MSC.enable')])
            ->getForm()
        ;

        $template->form = $form->createView();

        // Validate the verification code
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($authenticator->validateCode($user, $form->getData()['verify'])) {
                // Enable 2FA
                $user->useTwoFactor = '1';
                $user->save();

                return $this->redirectToRoute($request->get('_route'));
            }

            $template->message = $this->trans('ERR.invalidTwoFactor');
        }

        // Generate the secret
        if (!$user->secret) {
            $user->secret = random_bytes(128);
            $user->save();
        }

        $template->enable = true;
        $template->secret = Base32::encodeUpperUnpadded($user->secret);
        $template->qrCode = base64_encode($authenticator->getQrCode($user, $request));

        return null;
    }

    private function disableTwoFactor(FrontendUser $user, Request $request): ?Response
    {
        // Return if 2FA is disabled already
        if (!$user->useTwoFactor) {
            return null;
        }

        $user->secret = null;
        $user->useTwoFactor = '';
        $user->backupCodes = null;
        $user->save();

        // Clear all trusted devices
        $this->trustedDeviceManager->clearTrustedDevices($user);

        return $this->redirectToRoute($request->get('_route'));
    }

    private function getDisableForm(): \Symfony\Component\Form\FormInterface
    {
        return $this->createFormBuilder()
            ->add('request_token', ContaoRequestTokenType::class)
            ->add('submit', SubmitType::class, ['label' => $this->trans('MSC.disable')])
            ->getForm();
    }

    private function getClearForm(): \Symfony\Component\Form\FormInterface
    {
        return $this->createFormBuilder()
            ->add('request_token', ContaoRequestTokenType::class)
            ->add('submit', SubmitType::class, ['label' => $this->trans('MSC.clearTrustedDevices')])
            ->getForm();
    }

    private function trans(string $key): string
    {
        return $this->translator->trans($key, [], 'contao_default');
    }
}
