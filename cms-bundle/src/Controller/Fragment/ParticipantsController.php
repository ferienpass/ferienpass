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

use Doctrine\ORM\EntityManagerInterface;
use Ferienpass\CmsBundle\Controller\Frontend\AbstractController;
use Ferienpass\CoreBundle\Entity\Participant;
use Ferienpass\CoreBundle\Entity\User;
use Ferienpass\CoreBundle\Form\UserParticipantsType;
use Ferienpass\CoreBundle\Repository\ParticipantRepository;
use Ferienpass\CoreBundle\Ux\Flash;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ParticipantsController extends AbstractController
{

    public function __construct(private readonly ParticipantRepository $repository, private readonly EntityManagerInterface $entityManager)
    {
    }

    public function __invoke(Request $request): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        // TODO if originalParticipants.length eq 0 then add constraint {MinLength=1}
        $originalParticipants = $this->repository->findBy(['user' => $user]);
        $form = $this->createForm(UserParticipantsType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var iterable<int, Participant> $participants */
            $participants = $form->get('participants')->getData();

            foreach ($participants as $participant) {
                $this->entityManager->persist($participant);
            }

            $this->entityManager->flush();

            $this->addFlash(...Flash::confirmation()->text('Die Daten wurden erfolgreich gespeichert.')->create());

            return $this->redirectToRoute($request->attributes->get('_route'));
        }

        return $this->render('@FerienpassCms/fragment/user_account/participants.html.twig', [
            'participants' => $originalParticipants,
            'form' => $form,
        ]);
    }
}
