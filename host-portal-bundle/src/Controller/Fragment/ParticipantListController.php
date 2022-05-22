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
use Doctrine\Persistence\ManagerRegistry;
use Ferienpass\CoreBundle\Entity\Attendance;
use Ferienpass\CoreBundle\Entity\Offer;
use Ferienpass\CoreBundle\Facade\AttendanceFacade;
use Ferienpass\CoreBundle\Form\SimpleType\ContaoRequestTokenType;
use Ferienpass\CoreBundle\Ux\Flash;
use Ferienpass\HostPortalBundle\ApplicationSystem\ParticipantList;
use Ferienpass\HostPortalBundle\Dto\AddParticipantDto;
use Ferienpass\HostPortalBundle\Form\AddParticipantType;
use Ferienpass\HostPortalBundle\State\PrivacyConsent;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ParticipantListController extends AbstractFragmentController
{
    public function __construct(private PrivacyConsent $privacyConsent, private AttendanceFacade $attendanceFacade, private ManagerRegistry $doctrine, private ParticipantList $participantList)
    {
    }

    public function __invoke(Offer $offer, Request $request): Response
    {
        $user = $this->getUser();
        if (!$user instanceof FrontendUser) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        if ($this->isPrivacyStatementMissing($user)) {
            return $this->render('@FerienpassHostPortal/fragment/participant_list.html.twig', [
                'missingPrivacyStatement' => true,
            ]);
        }

        $edition = $offer->getEdition();
        if (null !== $edition && !$edition->isParticipantListReleased()) {
            return $this->render('@FerienpassHostPortal/fragment/participant_list.html.twig', [
                'notReleased' => true,
            ]);
        }

        $addForm = $this->createForm(AddParticipantType::class, $participantDto = new AddParticipantDto());
        $addForm->handleRequest($request);
        if ($addForm->isSubmitted() && $addForm->isValid()) {
            $this->denyAccessUnlessGranted('participants.add', $offer);

            $newParticipant = $participantDto->toEntity();
            $this->doctrine->getManager()->persist($newParticipant);

            $this->attendanceFacade->create($offer, $newParticipant);

            $this->addFlash(...Flash::confirmation()->text('Die Teilnehmer:in wurde auf die Teilnahmeliste geschrieben.')->create());

            return $this->redirect($request->getUri());
        }

        $statusForm = $this->createFormBuilder()
            ->add('confirm', SubmitType::class)
            ->add('reject', SubmitType::class)
            ->add('attendances', EntityType::class, [
                'class' => Attendance::class,
                'choices' => $offer->getAttendancesNotWithdrawn(),
                'choice_label' => 'id',
                'multiple' => true,
                'expanded' => true,
            ])
            ->add('request_token', ContaoRequestTokenType::class)
            ->getForm()
        ;

        $statusForm->handleRequest($request);
        if ($statusForm->isSubmitted() && $statusForm->isValid()) {
            $action = method_exists($statusForm, 'getClickedButton') ? $statusForm->getClickedButton()?->getName() : '';
            $this->denyAccessUnlessGranted('participants.'.$action, $offer);

            $attendances = $statusForm->get('attendances')->getData();

            switch ($action) {
                case 'confirm':
                    $this->participantList->confirm($attendances);

                    $this->addFlash(...Flash::confirmation()->text('Den Teilnehmer:innen wurde zugesagt.')->create());
                    break;
                case 'reject':
                    $this->participantList->reject($attendances);

                    $this->addFlash(...Flash::confirmation()->text('Den Teilnehmer:innen wurde abgesagt.')->create());
                    break;
            }

            return $this->redirect($request->getUri());
        }

        return $this->render('@FerienpassHostPortal/fragment/participant_list.html.twig', [
            'offer' => $offer,
            'addParticipant' => $addForm->createView(),
            'changeStatus' => $statusForm->createView(),
            'attendances' => $offer->getAttendancesNotWithdrawn(),
        ]);
    }

    private function isPrivacyStatementMissing(FrontendUser $user): bool
    {
        return !$this->privacyConsent->isSignedFor((int) $user->id);
    }
}
