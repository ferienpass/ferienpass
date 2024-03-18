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
use Ferienpass\AdminBundle\ApplicationSystem\ParticipantList;
use Ferienpass\AdminBundle\Breadcrumb\Breadcrumb;
use Ferienpass\AdminBundle\Form\MultiSelectType;
use Ferienpass\AdminBundle\State\PrivacyConsent;
use Ferienpass\CoreBundle\Entity\User;
use Ferienpass\CoreBundle\Export\ParticipantList\PdfExport;
use Ferienpass\CoreBundle\Facade\AttendanceFacade;
use Ferienpass\CoreBundle\Repository\OfferRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/angebote/{edition?null}/{id}/teilnahmeliste{_suffix}', name: 'admin_offer_participants', requirements: ['id' => '\d+'], defaults: ['_suffix' => ''])]
final class OfferParticipantsController extends AbstractController
{
    public function __construct(private readonly PrivacyConsent $privacyConsent, private readonly ParticipantList $participantList)
    {
    }

    public function __invoke(string $_suffix, int $id, OfferRepositoryInterface $offerRepository, Request $request, PdfExport $pdfExport, EntityManagerInterface $em, AttendanceFacade $attendanceFacade, Breadcrumb $breadcrumb, \Ferienpass\CoreBundle\Session\Flash $flash): Response
    {
        if (null === $offer = $offerRepository->find($id)) {
            throw $this->createNotFoundException();
        }

        $this->denyAccessUnlessGranted('participants.view', $offer);

        $_suffix = ltrim($_suffix, '.');
        if ('pdf' === $_suffix) {
            return $this->file($pdfExport->generate($offer), 'teilnahmeliste.pdf');
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        if ($this->isPrivacyStatementMissing($user)) {
            return $this->render('@FerienpassAdmin/page/offers/participant_list.html.twig', [
                'missingPrivacyStatement' => true,
            ]);
        }

        $items = $offer->getAttendancesNotWithdrawn();

        /** @var Form $ms */
        $ms = $this->createForm(MultiSelectType::class, options: [
            'buttons' => ['confirm', 'confirmAndInform', 'reject', 'rejectAndInform'],
            'items' => $items->toArray(),
        ]);

        $ms->handleRequest($request);
        if ($ms->isSubmitted() && $ms->isValid()) {
            $action = match ($ms->getClickedButton()->getName()) {
                'confirm', 'confirmAndInform' => 'confirm',
                'reject', 'rejectAndInform' => 'reject',
                default => throw new \InvalidArgumentException('Button not found'),
            };

            $this->denyAccessUnlessGranted("participants.$action", $offer);

            $notify = \in_array($ms->getClickedButton()->getName(), ['confirmAndInform', 'rejectAndInform'], true);
            $selectedParticipants = $ms->get('items')->getData()->toArray();

            if ('confirm' === $action) {
                $this->participantList->confirm($selectedParticipants, reorder: false, notify: $notify);

                $flash->addConfirmation(text: 'Den Teilnehmer:innen wurde zugesagt.');
            }

            if ('reject' === $action) {
                $this->participantList->reject($selectedParticipants, reorder: false, notify: $notify);

                $flash->addConfirmation(text: 'Den Teilnehmer:innen wurde abgesagt.');
            }

            return $this->redirectToRoute('admin_offer_participants', ['id' => $offer->getId(), 'edition' => $offer->getEdition()?->getAlias()]);
        }

        return $this->render('@FerienpassAdmin/page/offers/participant_list.html.twig', [
            'ms' => $ms,
            'items' => $items,
            'offer' => $offer,
            'breadcrumb' => $breadcrumb->generate(['offers.title', ['route' => 'admin_offers_index', 'routeParameters' => ['edition' => $offer->getEdition()->getAlias()]]], [$offer->getEdition()->getName(), ['route' => 'admin_offers_index', 'routeParameters' => ['edition' => $offer->getEdition()->getAlias()]]], $offer->getName(), 'Anmeldungen'),
        ]);

        //        if (null !== $edition && !$edition->isParticipantListReleased()) {
        //            return $this->render('@FerienpassAdmin/page/offers/participant_list.html.twig', [
        //                'notReleased' => true,
        //            ]);
        //        }

        // $this->denyAccessUnlessGranted('participants.add', $offer);
    }

    private function isPrivacyStatementMissing(User $user): bool
    {
        return !$this->privacyConsent->isSignedFor((int) $user->getId());
    }
}
