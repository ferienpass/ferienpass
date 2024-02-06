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

use Contao\CoreBundle\Exception\PageNotFoundException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Ferienpass\AdminBundle\Breadcrumb\Breadcrumb;
use Ferienpass\AdminBundle\Form\EditOfferType;
use Ferienpass\AdminBundle\Service\FileUploader;
use Ferienpass\CoreBundle\Entity\Edition;
use Ferienpass\CoreBundle\Entity\Offer;
use Ferienpass\CoreBundle\Ux\Flash;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Workflow\WorkflowInterface;

#[Route('/angebote/{edition?null}')]
final class OffersEditController extends AbstractController
{
    public function __construct(#[Autowire(service: 'ferienpass.file_uploader.offer')] private readonly FileUploader $fileUploader, private readonly ManagerRegistry $doctrine, private readonly WorkflowInterface $offerStateMachine)
    {
    }

    #[Route('/{id}/bearbeiten', name: 'admin_offers_edit', requirements: ['id' => '\d+'])]
    #[Route('/neu', name: 'admin_offers_new')]
    #[Route('/kopieren/{id}', name: 'admin_offers_copy')]
    #[Route('/variante/{id}', name: 'admin_offers_new_variant')]
    public function __invoke(#[MapEntity(id: 'id')] ?Offer $offer, #[MapEntity(mapping: ['edition' => 'alias'])] ?Edition $edition, EntityManagerInterface $em, Request $request, Breadcrumb $breadcrumb): Response
    {
        $offer = $this->getOffer($offer, $edition, $request);

        $form = $this->createForm(EditOfferType::class, $offer, ['is_variant' => !$offer->isVariantBase()]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Add alias to the change-set, later the {@see AliasListener.php} kicks in
            $offer->setAlias(uniqid());

            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $imageFileName = $this->fileUploader->upload($imageFile);
                $offer->setImage($imageFileName);
            }

            //            if ($imgCopyright = $form->get('imgCopyright')->getData()) {
            //                $fileModel = FilesModel::findByPk($offer->getImage());
            //                if (null !== $fileModel) {
            //                    /** @psalm-suppress UndefinedMagicPropertyAssignment */
            //                    $fileModel->imgCopyright = $imgCopyright;
            //                    $fileModel->save();
            //                }
            //            }

            $this->addFlash(...Flash::confirmation()->text('Die Daten wurden erfolgreich gespeichert.')->create());

            foreach ($this->offerStateMachine->getEnabledTransitions($offer) as $enabledTransition) {
                if (!$this->isGranted($enabledTransition->getName(), $offer)) {
                    continue;
                }

                $transitionButton = 'submitAnd'.ucfirst($enabledTransition->getName());
                if ($form->has($transitionButton) && ($button = $form->get($transitionButton)) && $button instanceof SubmitButton && $button->isClicked()) {
                    $this->offerStateMachine->apply($offer, $enabledTransition->getName());
                }
            }

            $em->flush();

            return $this->redirectToRoute('admin_offers_edit', array_filter(['id' => $offer->getId(), 'edition' => $offer->getEdition()?->getAlias()]));
        }

        return $this->render('@FerienpassAdmin/page/offers/edit.html.twig', [
            'item' => $offer,
            'form' => $form->createView(),
            'breadcrumb' => $breadcrumb->generate(['offers.title', ['route' => 'admin_offers_index', 'routeParameters' => array_filter(['edition' => $offer->getEdition()?->getAlias()])]], $offer->getEdition() ? [$offer->getEdition()->getName(), ['route' => 'admin_offers_index', 'routeParameters' => ['edition' => $offer->getEdition()->getAlias()]]] : [], $offer->getName().' (bearbeiten)'),
        ]);
    }

    private function getOffer(?Offer $offer, ?Edition $edition, Request $request): Offer
    {
        if ('admin_offers_edit' === $request->get('_route') && null === $offer) {
            throw new PageNotFoundException('Item not found');
        }

        if (null === $offer) {
            $offer = new Offer();
            $offer->setEdition($edition);

            $this->denyAccessUnlessGranted('create', $offer);

            $this->doctrine->getManager()->persist($offer);

            return $offer;
        }

        if ('admin_offers_copy' === $request->get('_route')) {
            $copy = new Offer();
            $copy->setEdition($edition);

            $this->denyAccessUnlessGranted('view', $offer);
            $this->denyAccessUnlessGranted('create', $copy);

            // TODO these properties should be read from the DTO of the current form
            $copy->setName($offer->getName().' (Kopie)');
            $copy->setDescription($offer->getDescription());
            $copy->setMeetingPoint($offer->getMeetingPoint());
            $copy->setBring($offer->getBring());
            $copy->setMinParticipants($offer->getMinParticipants());
            $copy->setMaxParticipants($offer->getMaxParticipants());
            $copy->setMinAge($offer->getMinAge());
            $copy->setMaxAge($offer->getMaxAge());
            $copy->setRequiresApplication($offer->requiresApplication());
            $copy->setOnlineApplication($offer->isOnlineApplication());
            $copy->setApplyText($offer->getApplyText());
            $copy->setContact($offer->getContact());
            $copy->setFee($offer->getFee());
            $copy->setImage($offer->getImage());

            $this->doctrine->getManager()->persist($copy);

            return $copy;
        }

        if ('admin_offers_new_variant' === $request->get('_route')) {
            $copy = new Offer();
            $copy->setEdition($edition);

            $this->denyAccessUnlessGranted('view', $offer);
            $this->denyAccessUnlessGranted('create', $copy);

            // TODO these properties should be read from the DTO of the current form
            $copy->setName($offer->getName().' (Kopie)');
            $copy->setDescription($offer->getDescription());
            $copy->setMeetingPoint($offer->getMeetingPoint());
            $copy->setBring($offer->getBring());
            $copy->setMinParticipants($offer->getMinParticipants());
            $copy->setMaxParticipants($offer->getMaxParticipants());
            $copy->setMinAge($offer->getMinAge());
            $copy->setMaxAge($offer->getMaxAge());
            $copy->setRequiresApplication($offer->requiresApplication());
            $copy->setOnlineApplication($offer->isOnlineApplication());
            $copy->setApplyText($offer->getApplyText());
            $copy->setContact($offer->getContact());
            $copy->setFee($offer->getFee());
            $copy->setImage($offer->getImage());

            $copy->setVariantBase($offer);

            $this->doctrine->getManager()->persist($copy);

            return $copy;
        }

        $this->denyAccessUnlessGranted('edit', $offer);

        return $offer;
    }
}
