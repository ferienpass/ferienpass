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
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Dbafs;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Ferienpass\AdminBundle\Breadcrumb\Breadcrumb;
use Ferienpass\AdminBundle\Form\EditOfferType;
use Ferienpass\AdminBundle\Service\FileUploader;
use Ferienpass\CoreBundle\Entity\Edition;
use Ferienpass\CoreBundle\Entity\Offer\OfferInterface;
use Ferienpass\CoreBundle\Entity\OfferDate;
use Ferienpass\CoreBundle\Repository\OfferRepositoryInterface;
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
final class OfferEditController extends AbstractController
{
    public function __construct(#[Autowire(service: 'ferienpass.file_uploader.offer_media')] private readonly FileUploader $fileUploader, #[Autowire(service: 'ferienpass.file_uploader.agreement_letters')] private readonly FileUploader $pdfFileUploader, private readonly ManagerRegistry $doctrine, private readonly WorkflowInterface $offerStateMachine, private readonly ContaoFramework $contaoFramework, private readonly OfferRepositoryInterface $offerRepository)
    {
    }

    #[Route('/{id}/bearbeiten', name: 'admin_offers_edit', requirements: ['id' => '\d+'])]
    #[Route('/neu', name: 'admin_offers_new')]
    #[Route('/kopieren/{id}', name: 'admin_offers_copy')]
    #[Route('/variante/{id}', name: 'admin_offers_new_variant')]
    public function __invoke(?int $id, #[MapEntity(mapping: ['edition' => 'alias'])] ?Edition $edition, EntityManagerInterface $em, Request $request, Breadcrumb $breadcrumb): Response
    {
        $offer = $this->getOffer($this->offerRepository->find($id), $edition, $request);

        $form = $this->createForm(EditOfferType::class, $offer, ['is_variant' => !$offer->isVariantBase()]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Add alias to the change-set, later the {@see AliasListener.php} kicks in
            $offer->setAlias(uniqid());

            $imageFile = $form->get('uploadImage')->getData();
            if ($imageFile) {
                $imgPath = $this->fileUploader->upload($imageFile);

                $this->contaoFramework->initialize();

                $fileModel = Dbafs::addResource($imgPath);

                $offer->setImage($fileModel->uuid);
            }

            if ($form->has('uploadAgreeLetter')) {
                $imageFile = $form->get('uploadAgreeLetter')->getData();
                if ($imageFile) {
                    $imgPath = $this->pdfFileUploader->upload($imageFile);

                    $this->contaoFramework->initialize();

                    $fileModel = Dbafs::addResource($imgPath);

                    $offer->setAgreementLetter($fileModel->uuid);
                }
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

    private function getOffer(?OfferInterface $offer, ?Edition $edition, Request $request): OfferInterface
    {
        if ('admin_offers_edit' === $request->get('_route') && null === $offer) {
            throw new PageNotFoundException('Item not found');
        }

        if (null === $offer) {
            $offer = $this->offerRepository->createNew();
            $offer->setEdition($edition);
            $offer->addDate(new OfferDate($offer));

            $this->denyAccessUnlessGranted('create', $offer);

            $this->doctrine->getManager()->persist($offer);

            return $offer;
        }

        if ('admin_offers_copy' === $request->get('_route')) {
            $copy = $this->offerRepository->createCopy($offer);
            $copy->setEdition($edition);

            $this->denyAccessUnlessGranted('view', $offer);
            $this->denyAccessUnlessGranted('create', $copy);

            $this->doctrine->getManager()->persist($copy);

            return $copy;
        }

        if ('admin_offers_new_variant' === $request->get('_route')) {
            $copy = $this->offerRepository->createVariant($offer);
            $copy->setEdition($edition);

            $this->denyAccessUnlessGranted('view', $offer);
            $this->denyAccessUnlessGranted('create', $copy);

            $this->doctrine->getManager()->persist($copy);

            return $copy;
        }

        $this->denyAccessUnlessGranted('edit', $offer);

        return $offer;
    }
}
