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

use Contao\CoreBundle\OptIn\OptIn;
use Doctrine\ORM\EntityManagerInterface;
use Ferienpass\AdminBundle\Form\AcceptInvitationType;
use Ferienpass\CoreBundle\Entity\Host;
use Ferienpass\CoreBundle\Entity\User;
use Ferienpass\CoreBundle\Repository\HostRepository;
use Ferienpass\CoreBundle\Repository\UserRepository;
use Ferienpass\CoreBundle\Ux\Flash;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/einladung/{email}/{host}', name: 'admin_invitation')]
final class FollowInvitationController extends AbstractController
{
    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher, private readonly OptIn $optIn, private readonly HostRepository $hostRepository)
    {
    }

    public function __invoke(string $email, #[MapEntity(mapping: ['host' => 'alias'])] ?Host $host, UserRepository $userRepository, EntityManagerInterface $em, Request $request, UriSigner $uriSigner)
    {
        if (!$uriSigner->checkRequest($request)) {
            throw new NotFoundHttpException();
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            $user = new User();
        }

        $form = $this->createForm(AcceptInvitationType::class, $user);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if (!$user->getId()) {
                $user->setPassword($this->passwordHasher->hashPassword($user, $user->getPlainPassword() ?? ''));
                $host->addMember($user);

                $this->addFlash(...Flash::confirmation()->text('Account erstellt. Bitte melden Sie sich nun mit Ihrer E-Mail-Adresse an.')->create());

                $em->persist($user);
                $em->flush();

                return $this->redirectToRoute('admin_index');
            }

            $host->addMember($user);
            $em->flush();

            $this->addFlash(...Flash::confirmation()->text('Einladung angenommen')->create());

            return $this->redirectToRoute('admin_index');
        }

        return $this->render('@FerienpassAdmin/page/login/follow_invitation.html.twig', [
            'host' => $host,
            'form' => $form->createView(),
            'invitee_email' => $email,
        ]);
    }
}
