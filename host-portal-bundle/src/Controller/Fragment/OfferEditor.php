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

use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\Slug\Slug;
use Contao\Dbafs;
use Contao\FilesModel;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Ferienpass\CoreBundle\Entity\Offer;
use Ferienpass\CoreBundle\Entity\OfferDate;
use Ferienpass\CoreBundle\Repository\EditionRepository;
use Ferienpass\CoreBundle\Ux\Flash;
use Ferienpass\HostPortalBundle\Form\EditOfferType;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class OfferEditor extends AbstractFragmentController
{
    private Slug $slug;
    private string $imagesDir;
    private string $projectDir;
    private EditionRepository $editionRepository;

    public function __construct(Slug $slug, string $imagesDir, string $projectDir, EditionRepository $editionRepository)
    {
        $this->slug = $slug;
        $this->imagesDir = $imagesDir;
        $this->projectDir = $projectDir;
        $this->editionRepository = $editionRepository;
    }

    public function __invoke(Request $request): Response
    {
        $offer = $this->getOffer($request);

        /** @var Collection<int, OfferDate> $originalDates */
        $originalDates = new ArrayCollection();
        foreach ($offer->getDates() as $date) {
            $originalDates->add($date);
        }

        $form = $this->createForm(EditOfferType::class, $offer, ['is_variant' => !$offer->isVariantBase()]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $offer->setTimestamp(time());

            $entityManager = $this->getDoctrine()->getManager();

            foreach ($originalDates as $date) {
                if (false === $offer->getDates()->contains($date)) {
                    $entityManager->remove($date);
                }
            }

            /** @var UploadedFile $imageFile */
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);

                $fileExists = fn (string $filename) => file_exists(sprintf('%s/%s.%s', $this->imagesDir, $filename, $imageFile->guessExtension()));
                $safeFilename = $this->slug->generate($originalFilename, [], $fileExists);
                $newFilename = $safeFilename.'.'.$imageFile->guessExtension();

                try {
                    $imageFile->move($this->imagesDir, $newFilename);

                    $relativeFileName = ltrim(str_replace($this->projectDir, '', $this->imagesDir), '/').'/'.$newFilename;
                    $fileModel = Dbafs::addResource($relativeFileName);
                    $fileModel->imgCopyright = $form->get('imgCopyright')->getData();
                    $fileModel->save();

                    $offer->setImage($fileModel->uuid);
                } catch (FileException $e) {
                }
            } elseif ($imgCopyright = $form->get('imgCopyright')->getData()) {
                $fileModel = FilesModel::findByPk($offer->getImage());
                if (null !== $fileModel) {
                    $fileModel->imgCopyright = $form->get('imgCopyright')->getData();
                    $fileModel->save();
                }
            }

            $entityManager->flush();

            $this->addFlash(...Flash::confirmation()->text('Die Daten wurden erfolgreich gespeichert.')->create());

            return $this->redirectToRoute($request->get('_route'), ['id' => $offer->getId()]);
        }

        return $this->render('@FerienpassHostPortal/fragment/offer_editor.html.twig', [
            'offer' => $offer,
            'form' => $form->createView(),
        ]);
    }

    private function getOffer(Request $request): Offer
    {
        if (0 === $offerId = $request->attributes->getInt('id')) {
            $offer = new Offer();

            if ($request->query->has('edition')) {
                $edition = $this->editionRepository->findOneBy(['alias' => $request->query->get('edition')]);
            }

            if (null === ($edition ?? null)) {
                $edition = $this->editionRepository->findDefaultForHost();
            }

            $offer->setEdition($edition);
            // TODO use ->isGranted() and show an error message
            $this->denyAccessUnlessGranted('create', $offer);

            if ($request->query->has('act') && $request->query->has('source')) {
                $source = $this->getDoctrine()->getRepository(Offer::class)->find($request->query->getInt('source'));
                if (null !== $source) {
                    $this->denyAccessUnlessGranted('view', $source);

                    // TODO these properties should be read from the DTO of the current form
                    $offer->setName($source->getName());
                    $offer->setDescription($source->getDescription());
                    $offer->setMeetingPoint($source->getMeetingPoint());
                    $offer->setBring($source->getBring());
                    $offer->setMinParticipants($source->getMinParticipants());
                    $offer->setMaxParticipants($source->getMaxParticipants());
                    $offer->setMinAge($source->getMinAge());
                    $offer->setMaxAge($source->getMaxAge());
                    $offer->setRequiresApplication($source->requiresApplication());
                    $offer->setOnlineApplication($source->isOnlineApplication());
                    $offer->setApplyText($source->getApplyText());
                    $offer->setContact($source->getContact());
                    $offer->setFee($source->getFee());
                    $offer->setImage($source->getImage());
                }

                if ('newVariant' === $request->query->get('act')) {
                    $offer->setVariantBase($source);
                }
            }

            $this->getDoctrine()->getManager()->persist($offer);

            return $offer;
        }

        $offer = $this->getDoctrine()->getRepository(Offer::class)->find($offerId);
        if (null === $offer) {
            throw new PageNotFoundException('Item not found');
        }

        $this->denyAccessUnlessGranted('edit', $offer);

        return $offer;
    }
}
