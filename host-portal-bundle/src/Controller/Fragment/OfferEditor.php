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
use Doctrine\Persistence\ManagerRegistry;
use Ferienpass\CoreBundle\Entity\Edition;
use Ferienpass\CoreBundle\Entity\Offer;
use Ferienpass\CoreBundle\Ux\Flash;
use Ferienpass\HostPortalBundle\Dto\EditOfferDto;
use Ferienpass\HostPortalBundle\Form\EditOfferType;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class OfferEditor extends AbstractFragmentController
{
    public function __construct(private Slug $slug, private string $imagesDir, private string $projectDir, private ManagerRegistry $doctrine)
    {
    }

    public function __invoke(Request $request): Response
    {
        $offer = $this->getOffer($request);

        $form = $this->createForm(EditOfferType::class, $offerDto = EditOfferDto::fromEntity($offer), ['is_variant' => !$offer->isVariantBase()]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $offer = $offerDto->toEntity($offer);
            $offer->setTimestamp(time());

            // Add alias to the change-set, later the {@see AliasListener.php} kicks in
            $offer->setAlias(uniqid());
            // Add fields to the change-set, later the {@see SortingFieldsListener.php} kicks in
            $offer->setDatesSorting(random_int(0, 99999));
            $offer->setHostsSorting(uniqid());

            $em = $this->doctrine->getManager();

            /** @var UploadedFile|null $imageFile */
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), \PATHINFO_FILENAME);

                $fileExists = fn (string $filename): bool => file_exists(sprintf('%s/%s.%s', $this->imagesDir, $filename, (string) $imageFile->guessExtension()));
                $safeFilename = $this->slug->generate($originalFilename, [], $fileExists);
                $newFilename = $safeFilename.'.'.(string) $imageFile->guessExtension();

                try {
                    $imageFile->move($this->imagesDir, $newFilename);

                    $relativeFileName = ltrim(str_replace($this->projectDir, '', $this->imagesDir), '/').'/'.$newFilename;
                    $fileModel = Dbafs::addResource($relativeFileName);
                    /** @psalm-suppress UndefinedMagicPropertyAssignment */
                    $fileModel->imgCopyright = $form->get('imgCopyright')->getData() ?? '';
                    $fileModel->save();

                    $offer->setImage($fileModel->uuid);
                } catch (FileException) {
                }
            } elseif ($imgCopyright = $form->get('imgCopyright')->getData()) {
                $fileModel = FilesModel::findByPk($offer->getImage());
                if (null !== $fileModel) {
                    /** @psalm-suppress UndefinedMagicPropertyAssignment */
                    $fileModel->imgCopyright = $imgCopyright;
                    $fileModel->save();
                }
            }

            $em->flush();

            $this->addFlash(...Flash::confirmation()->text('Die Daten wurden erfolgreich gespeichert.')->create());

            return $this->redirectToRoute($request->attributes->get('_route'), ['id' => $offer->getId()]);
        }

        return $this->renderForm('@FerienpassHostPortal/fragment/offer_editor.html.twig', [
            'offer' => $offer,
            'form' => $form,
        ]);
    }

    private function getOffer(Request $request): Offer
    {
        if (0 === $offerId = $request->attributes->getInt('id')) {
            $offer = new Offer();

            $edition = null;
            if ($alias = $request->query->get('edition')) {
                $edition = $this->doctrine->getRepository(Edition::class)->findOneBy(['alias' => $alias]);
            }

            if (null !== $edition) {
                $offer->setEdition($edition);
            }

            $this->denyAccessUnlessGranted('create', $offer);

            if ($request->query->has('act') && $request->query->has('source')) {
                $source = $this->doctrine->getRepository(Offer::class)->find($request->query->getInt('source'));
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

            $this->doctrine->getManager()->persist($offer);

            return $offer;
        }

        $offer = $this->doctrine->getRepository(Offer::class)->find($offerId);
        if (null === $offer) {
            throw new PageNotFoundException('Item not found');
        }

        $this->denyAccessUnlessGranted('edit', $offer);

        return $offer;
    }
}
