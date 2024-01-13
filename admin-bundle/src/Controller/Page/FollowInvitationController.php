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
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/einladung', name: 'admin_invitation')]
final class FollowInvitationController extends AbstractController
{
    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher, private readonly OptIn $optIn, private readonly HostRepository $hostRepository)
    {
    }

    public function __invoke(UserRepository $userRepository, EntityManagerInterface $em, Request $request)
    {
        // Find an unconfirmed token
        if ((!$optInToken = $this->optIn->find((string) $request->query->get('token')))
            || !$optInToken->isValid()
            || \count($relatedRecords = $optInToken->getRelatedRecords()) < 1
            || !\array_key_exists('Host', $relatedRecords)
            || !\array_key_exists('tl_member', $relatedRecords)) {
            $error = 'MSC.invalidToken';

            return $this->render('@FerienpassAdmin/fragment/follow_invitation.html.twig', ['error' => $error]);
        }

        if ($optInToken->isConfirmed()) {
            $error = 'MSC.tokenConfirmed';

            return $this->render('@FerienpassAdmin/fragment/follow_invitation.html.twig', ['error' => $error]);
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            $user = new User();
        }

        $form = $this->createForm(AcceptInvitationType::class, $user);

        $hostId = reset($relatedRecords['Host']);
        $inviter = reset($relatedRecords['tl_member']);

        /** @var Host $host */
        $host = $this->hostRepository->find($hostId);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if (!$user->getId()) {
                $user->setPassword($this->passwordHasher->hashPassword($user, $user->getPassword() ?? ''));
                $host->addMember($user);

                $this->addFlash(...Flash::confirmation()->text('Account erstellt. Bitte melden Sie sich nun mit Ihrer E-Mail-Adresse an.')->create());

                $optInToken->confirm();

                $em->persist($user);
                $em->flush();

                return $this->redirect('/');
            }

            $host->addMember($user);
            $em->flush();

            $optInToken->confirm();

            $this->addFlash(...Flash::confirmation()->text('Einladung angenommen')->create());

            return $this->redirect('/');
        }

        return $this->render('@FerienpassAdmin/fragment/follow_invitation.html.twig', [
            'member' => $userRepository->find($inviter),
            'host' => $host,
            'form' => $form,
            'invitee_email' => $optInToken->getEmail(),
        ]);
    }
}
