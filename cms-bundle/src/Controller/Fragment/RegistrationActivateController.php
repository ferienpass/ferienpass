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
use Ferienpass\CoreBundle\Message\AccountActivated;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;

class RegistrationActivateController extends AbstractController
{
    public function __construct(private readonly Security $security, private readonly MessageBusInterface $messageBus)
    {
    }

    public function __invoke(User $user, EntityManagerInterface $em, Request $request): Response
    {
        $user->setDisabled(false);

        $em->flush();

        $this->messageBus->dispatch(new AccountActivated($user->getId()));

        $this->security->login($user);

        return $this->redirectToRoute('registration_welcome');
    }
}
