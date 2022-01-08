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

use Doctrine\Persistence\ManagerRegistry;
use Ferienpass\CoreBundle\Entity\Offer;
use Ferienpass\CoreBundle\Export\Offer\PrintSheet\PdfExports;
use Ferienpass\CoreBundle\Message\OfferCancelled;
use Ferienpass\CoreBundle\Message\OfferRelaunched;
use Ferienpass\CoreBundle\Ux\Flash;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;

final class OfferDetailsController extends AbstractFragmentController
{
    private PdfExports $pdfExports;
    private ManagerRegistry $doctrine;
    private MessageBusInterface $messageBus;

    public function __construct(PdfExports $pdfExports, ManagerRegistry $doctrine, MessageBusInterface $messageBus)
    {
        $this->pdfExports = $pdfExports;
        $this->doctrine = $doctrine;
        $this->messageBus = $messageBus;
    }

    public function __invoke(Offer $offer, Request $request): Response
    {
        $em = $this->doctrine->getManager();

        if ($request->isMethod('delete')) {
            $this->denyAccessUnlessGranted('delete', $offer);

            // Do not delete variants
            if ($offer->isVariantBase() && !$offer->getVariants()->isEmpty()) {
                /** @var Offer $firstVariant */
                $firstVariant = $offer->getVariants()->first();

                $firstVariant->setVariantBase(null);

                /** @var Offer $variant */
                foreach ($offer->getVariants() as $variant) {
                    if ($variant === $firstVariant) {
                        continue;
                    }

                    $variant->setVariantBase($firstVariant);
                }
            }

            $em->remove($offer);
            $em->flush();

            $this->addFlash(...Flash::confirmation()->text('Das Angebot wurde gelöscht.')->create());

            return $this->redirectToRoute('host_offer_list');
        }

        if ($request->isMethod('post') && 'cancel' === $request->get('act')) {
            $this->denyAccessUnlessGranted('cancel', $offer);

            $offer->setCancelled(true);
            $em->flush();

            $this->messageBus->dispatch(new OfferCancelled($offer->getId()));

            $this->addFlash(...Flash::confirmation()->text('Das Angebot wurde abgesagt.')->create());

            return $this->redirect($request->getUri());
        }

        if ($request->isMethod('post') && 'relaunch' === $request->get('act')) {
            $this->denyAccessUnlessGranted('relaunch', $offer);

            $offer->setCancelled(false);
            $em->flush();

            // Whether the original participants should be reactivated or whether the participant list should be discarded
            $restoreParticipants = $request->request->getBoolean('participants_restore');

            $this->messageBus->dispatch(new OfferRelaunched($offer->getId()));

            $this->addFlash(...Flash::confirmation()->text('Das Angebot wurde wiederhergestellt.')->create());

            return $this->redirect($request->getUri());
        }

        $this->denyAccessUnlessGranted('view', $offer);

        return $this->render('@FerienpassHostPortal/fragment/offer_details.html.twig', [
            'offer' => $offer,
            'hasPdf' => $this->pdfExports->has(),
        ]);
    }
}
