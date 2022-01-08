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

namespace Ferienpass\HostPortalBundle\Controller;

use Contao\StringUtil;
use Doctrine\Common\Collections\Collection;
use Ferienpass\CoreBundle\Entity\Offer;
use Ferienpass\CoreBundle\Export\ParticipantList\PdfExport;
use Ferienpass\CoreBundle\Repository\ParticipantRepository;
use Ferienpass\CoreBundle\Ux\Flash;
use Ferienpass\HostPortalBundle\ApplicationSystem\ParticipantList;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/angebot/{id}/teilnahmeliste", requirements={"id"="\d+"})
 */
final class ParticipantListController extends AbstractController
{
    private ParticipantList $participantList;
    private PdfExport $pdfExport;

    public function __construct(ParticipantList $participantList, PdfExport $pdfExport)
    {
        $this->participantList = $participantList;
        $this->pdfExport = $pdfExport;
    }

    /**
     * @Route("/zusagen", name="confirm-participants", methods={"POST"})
     */
    public function confirm(Offer $offer, Request $request, ParticipantRepository $participantRepository): Response
    {
        $this->initializeContaoFramework();
        $this->checkToken();

        if (!$this->isGranted('participants.confirm', $offer)) {
            $this->addFlash(...Flash::error()->text('Die Aktion kann nicht ausgeführt werden.')->create());

            return $this->redirectToRoute('host_participant_list', ['id' => $offer->getId()]);
        }

        $participantIds = StringUtil::trimsplit(',', $request->request->get('participants'));
        $participants = $participantRepository->findBy(['id' => $participantIds]);
        \assert($participants instanceof Collection);

        $this->participantList->confirm($offer, $participants);

        $this->addFlash(...Flash::confirmation()->text('Den Teilnehmer:innen wurde zugesagt.')->create());

        return $this->redirectToRoute('host_participant_list', ['id' => $offer->getId()]);
    }

    /**
     * @Route("/absagen", name="reject-participants", methods={"POST"})
     */
    public function reject(Offer $offer, Request $request, ParticipantRepository $participantRepository): Response
    {
        $this->initializeContaoFramework();
        $this->checkToken();

        if (!$this->isGranted('participants.reject', $offer)) {
            $this->addFlash(...Flash::error()->text('Die Aktion kann nicht ausgeführt werden.')->create());

            return $this->redirectToRoute('host_participant_list', ['id' => $offer->getId()]);
        }

        $participantIds = StringUtil::trimsplit(',', $request->request->get('participants'));
        $participants = $participantRepository->findBy(['id' => $participantIds]);
        \assert($participants instanceof Collection);

        $this->participantList->reject($offer, $participants);

        $this->addFlash(...Flash::confirmation()->text('Den Teilnehmer:innen wurde abgesagt.')->create());

        return $this->redirectToRoute('host_participant_list', ['id' => $offer->getId()]);
    }
}
