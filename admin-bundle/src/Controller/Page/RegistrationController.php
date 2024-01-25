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
use Ferienpass\AdminBundle\Dto\HostRegistrationDto;
use Ferienpass\AdminBundle\Form\HostRegistrationType;
use Ferienpass\CoreBundle\Message\HostCreated;
use Ferienpass\CoreBundle\Ux\Flash;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/registrierung', name: 'admin_registration')]
final class RegistrationController extends AbstractController
{
    public function __invoke(Request $request, EntityManagerInterface $em, MessageBusInterface $messageBus, UserPasswordHasherInterface $passwordHasher): Response
    {
        $dto = new HostRegistrationDto();
        $form = $this->createForm(HostRegistrationType::class, $dto);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $user = $dto->toUser();
            $user->setRoles(['ROLE_HOST']);
            $user->setDisabled();

            $user->setPassword($passwordHasher->hashPassword($user, $user->getPlainPassword()));

            // TODO check for uniqueness
            $em->persist($user);

            $host = $dto->toHost();
            $host->addMember($user);
            $em->persist($host);
            $em->flush();

            $messageBus->dispatch(new HostCreated($host->getId(), $user->getId()));

            $this->addFlash(...Flash::confirmationModal()->headline('Registrierung gesendet')->text('Ihre Registrierung haben wir erhalten. Wir werden sie schnellstmöglich bearbeiten. Sie bekommen von uns eine Mitteilung.')->linkText('Zur Startseite')->create());

            return $this->redirectToRoute($request->attributes->get('_route'));
        }

        return $this->render('@FerienpassAdmin/page/login/registration.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
