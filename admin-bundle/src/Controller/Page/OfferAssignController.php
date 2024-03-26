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

use Doctrine\Persistence\ManagerRegistry;
use Ferienpass\AdminBundle\Breadcrumb\Breadcrumb;
use Ferienpass\CoreBundle\Export\ParticipantList\PdfExport;
use Ferienpass\CoreBundle\Export\ParticipantList\WordExport;
use Ferienpass\CoreBundle\Repository\OfferRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/angebote/{edition?null}/{id}/zuordnen', requirements: ['id' => '\d+'])]
class OfferAssignController extends AbstractController
{
    public function __construct(private readonly PdfExport $pdfExport, private readonly WordExport $wordExport)
    {
    }

    #[Route('', name: 'admin_offer_assign')]
    public function __invoke(int $id, Request $request, OfferRepositoryInterface $offerRepository, Session $session, ManagerRegistry $doctrine, Breadcrumb $breadcrumb): Response
    {
        if (null === $offer = $offerRepository->find($id)) {
            throw $this->createNotFoundException();
        }

        if (!$offer->getEdition()->hostsCanAssign()) {
            $this->denyAccessUnlessGranted('ROLE_ADMIN');
        }

        $autoAssign = $session->get('admin--auto-assign', false);

        $toggleMode = $this->createFormBuilder(['auto' => $autoAssign])
            ->add('auto', CheckboxType::class, ['false_values' => ['']])
            ->getForm()
        ;

        $toggleMode->handleRequest($request);
        if ($toggleMode->isSubmitted() && $toggleMode->isValid()) {
            $autoAssign = $toggleMode->get('auto')->getData();

            $session->set('admin--auto-assign', $autoAssign);
        }

        /** @noinspection FormViewTemplate `createView()` messes ups error handling/redirect */
        return $this->render('@FerienpassAdmin/page/offers/assign.html.twig', [
            'offer' => $offer,
            'toggleMode' => $toggleMode,
            'autoAssign' => $autoAssign,
            'emails' => array_unique(array_filter([])),
            'hasWordExport' => $this->wordExport->hasTemplate(),
            'breadcrumb' => $breadcrumb->generate(['offers.title', ['route' => 'admin_offers_index', 'routeParameters' => ['edition' => $offer->getEdition()->getAlias()]]], [$offer->getEdition()->getName(), ['route' => 'admin_offers_index', 'routeParameters' => ['edition' => $offer->getEdition()->getAlias()]]], $offer->getName(), 'Anmeldungen'),
        ]);
    }

    #[Route('.pdf', name: 'admin_offer_assign_pdf')]
    public function pdf(int $id, OfferRepositoryInterface $offerRepository): Response
    {
        if (null === $offer = $offerRepository->find($id)) {
            throw $this->createNotFoundException();
        }

        $path = $this->pdfExport->generate($offer);

        return $this->file($path, 'teilnahmeliste.pdf');
    }

    #[Route('.docx', name: 'admin_offer_assign_docx')]
    public function docx(int $id, OfferRepositoryInterface $offerRepository): Response
    {
        if (null === $offer = $offerRepository->find($id)) {
            throw $this->createNotFoundException();
        }

        $path = $this->wordExport->generate($offer);

        return $this->file($path, 'teilnahmeliste.docx');
    }
}
