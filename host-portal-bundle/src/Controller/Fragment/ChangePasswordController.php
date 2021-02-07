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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

final class ChangePasswordController extends AbstractFragmentController
{
    private EncoderFactoryInterface $encoderFactory;
    private TranslatorInterface $translator;
    private Security $security;

    public function __construct(EncoderFactoryInterface $encoderFactory, TranslatorInterface $translator, Security $security)
    {
        $this->encoderFactory = $encoderFactory;
        $this->translator = $translator;
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
            $user->password = $this->encoderFactory->getEncoder(FrontendUser::class)->encodePassword($form->getData()['password'], null);
            $user->save();

            $this->addFlash('confirmation', $this->trans('MSC.newPasswordSet'));
        }

        return $this->render('@FerienpassHostPortal/fragment/change_password.html.twig', ['form' => $form->createView()]);
    }

    private function trans(string $key): string
    {
        return $this->translator->trans($key, [], 'contao_default');
    }
}
