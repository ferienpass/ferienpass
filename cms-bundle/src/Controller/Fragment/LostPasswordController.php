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
use Ferienpass\CmsBundle\Form\ResetPasswordRequestFormType;
use Ferienpass\CmsBundle\Message\UserLostPassword;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use SymfonyCasts\Bundle\ResetPassword\Controller\ResetPasswordControllerTrait;

class LostPasswordController extends AbstractController
{
    use ResetPasswordControllerTrait;

    public function __construct(private readonly MessageBusInterface $messageBus)
    {
    }

    public function __invoke(Request $request): Response
    {
        $form = $this->createForm(ResetPasswordRequestFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->messageBus->dispatch(new UserLostPassword($form->get('email')->getData()));

            return $this->redirectToRoute('lost_password', ['method' => 'requested']);
        }

        return $this->render('@FerienpassCms/fragment/reset_password/request.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
