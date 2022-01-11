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

use Contao\CoreBundle\Slug\Slug;
use Contao\Dbafs;
use Doctrine\Persistence\ManagerRegistry;
use Ferienpass\CoreBundle\Entity\Host;
use Ferienpass\CoreBundle\Ux\Flash;
use Ferienpass\HostPortalBundle\Dto\EditHostDto;
use Ferienpass\HostPortalBundle\Form\EditHostType;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class OrganizationEditorController extends AbstractFragmentController
{
    public function __construct(private Slug $slug, private string $logosDir, private string $projectDir, private ManagerRegistry $doctrine)
    {
    }

    public function __invoke(Host $host, Request $request): ?Response
    {
        $this->denyAccessUnlessGranted('edit', $host);

        $form = $this->createForm(EditHostType::class, $hostDto = EditHostDto::fromEntity($host));
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $host = $hostDto->toEntity($host);
            $host->setTimestamp(time());

            /** @var UploadedFile $logoFile */
            $logoFile = $form->get('logo')->getData();
            if ($logoFile) {
                $originalFilename = pathinfo($logoFile->getClientOriginalName(), \PATHINFO_FILENAME);

                $fileExists = fn (string $filename) => file_exists(sprintf('%s/%s.%s', $this->logosDir, $filename, $logoFile->guessExtension()));
                $safeFilename = $this->slug->generate($originalFilename, [], $fileExists);
                $newFilename = $safeFilename.'.'.$logoFile->guessExtension();

                try {
                    $logoFile->move($this->logosDir, $newFilename);

                    $relativeFileName = ltrim(str_replace($this->projectDir, '', $this->logosDir), '/').'/'.$newFilename;
                    $fileModel = Dbafs::addResource($relativeFileName);

                    $host->setLogo($fileModel->uuid);
                } catch (FileException) {
                }
            }

            $this->doctrine->getManager()->flush();

            $this->addFlash(...Flash::confirmation()->text('Die Daten wurden erfolgreich gespeichert.')->create());

            return $this->redirectToRoute($request->get('_route'), ['id' => $host->getId()]);
        }

        return $this->renderForm('@FerienpassHostPortal/fragment/organization_editor.html.twig', [
            'host' => $host,
            'form' => $form,
        ]);
    }
}
