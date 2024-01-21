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
use Contao\CoreBundle\Slug\Slug;
use Contao\Dbafs;
use Contao\FilesModel;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Ferienpass\AdminBundle\Breadcrumb\Breadcrumb;
use Ferienpass\AdminBundle\Form\EditOfferType;
use Ferienpass\CoreBundle\Entity\Edition;
use Ferienpass\CoreBundle\Entity\Offer;
use Ferienpass\CoreBundle\Ux\Flash;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;

#[Route('/{edition}/angebote')]
#[AsLiveComponent(name: 'OffersEdit', template: '@FerienpassAdmin/components/EditOffer.html.twig')]
final class OffersEditController extends AbstractController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;

    #[LiveProp]
    public Offer $initialFormData;

    public function __construct(private readonly Slug $slug, #[Autowire('%contao.upload_path%/img')] private readonly string $imagesDir, #[Autowire('%kernel.project_dir%')] private readonly string $projectDir, private readonly ManagerRegistry $doctrine, private readonly FormFactoryInterface $formFactory)
    {
    }

    #[Route('/{id}/bearbeiten', name: 'admin_offers_edit', requirements: ['id' => '\d+'])]
    #[Route('/neu', name: 'admin_offers_new')]
    public function __invoke(#[MapEntity(mapping: ['edition' => 'alias'])] ?Edition $edition, EntityManagerInterface $em, Request $request, Breadcrumb $breadcrumb): Response
    {
        $offer = $this->initialFormData = $this->getOffer($request, $edition);

        $form = $this->instantiateForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Add alias to the change-set, later the {@see AliasListener.php} kicks in
            $offer->setAlias(uniqid());

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

        return $this->render('@FerienpassAdmin/page/offers/edit.html.twig', [
            'item' => $offer,
            'form' => $form,
            'breadcrumb' => $breadcrumb->generate([$offer->getEdition()->getName(), ['route' => 'admin_offers_index', 'routeParameters' => ['edition' => $offer->getEdition()->getAlias()]]], $offer->getName().' (bearbeiten)'),
        ]);
    }

    protected function instantiateForm(): FormInterface
    {
        return $this->formFactory->create(EditOfferType::class, $this->initialFormData, ['is_variant' => !$this->initialFormData->isVariantBase()]);
    }

    private function getOffer(Request $request, ?Edition $edition): Offer
    {
        if (0 === $offerId = $request->attributes->getInt('id')) {
            $offer = new Offer();
            $offer->setEdition($edition);

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
