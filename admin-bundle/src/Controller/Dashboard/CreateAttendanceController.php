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

namespace Ferienpass\AdminBundle\Controller\Dashboard;

use Doctrine\Persistence\ManagerRegistry;
use Ferienpass\CoreBundle\Entity\Offer;
use Ferienpass\CoreBundle\Entity\Participant;
use Ferienpass\CoreBundle\Facade\AttendanceFacade;
use Ferienpass\CoreBundle\Form\BackendApplicationType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CreateAttendanceController extends AbstractController
{
    public function __construct(private readonly AttendanceFacade $attendanceFacade, private readonly ManagerRegistry $doctrine, private readonly FormFactoryInterface $formFactory)
    {
    }

    public function __invoke(Request $request): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        $form = $this->formFactory->create(BackendApplicationType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $offer = $form->get('offer')->getData();
            $participant = $form->get('participant')->getData();
            $status = $form->get('status')->getData();
            $notify = (bool) $form->get('notify')->getData();

            \assert($offer instanceof Offer);

            if (!$participant instanceof Participant) {
                $participant = new Participant();
                $participant->setFirstname($form->get('firstname')->getData());
                $participant->setLastname($form->get('lastname')->getData());
                $participant->setDateOfBirth($form->get('dateOfBirth')->getData());
                $participant->setPhone($form->get('phone')->getData());
                $participant->setMobile($form->get('mobile')->getData());
                $participant->setEmail($form->get('email')->getData());

                $this->doctrine->getManager()->persist($participant);
            }

            $this->attendanceFacade->create($offer, $participant, $status, $notify);

            return $this->redirectToRoute('backend_offer_applications', ['id' => $offer->getId()]);
        }

        return $this->render('@FerienpassAdmin/fragment/dashboard/create_attendance.html.twig', [
            'application' => $form->createView(),
        ]);
    }
}
