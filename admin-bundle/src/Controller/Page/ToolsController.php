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

use Ferienpass\AdminBundle\Breadcrumb\Breadcrumb;
use Ferienpass\CoreBundle\Facade\EraseDataFacade;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class ToolsController extends AbstractController
{
    #[Route('/tools', name: 'admin_tools')]
    #[IsGranted('ROLE_ADMIN')]
    public function index(Breadcrumb $breadcrumb): Response
    {
        return $this->render('@FerienpassAdmin/page/tools/index.html.twig', [
            'breadcrumb' => $breadcrumb->generate('tools.title'),
        ]);
    }

    #[IsGranted('ROLE_ADMIN')]
    #[Route('/export', name: 'admin_export_index')]
    public function export(Breadcrumb $breadcrumb): Response
    {
        return $this->render('@FerienpassAdmin/page/tools/export.html.twig', [
            'breadcrumb' => $breadcrumb->generate(['tools.title', ['route' => 'admin_tools']], 'export.title'),
        ]);
    }

    #[Route('/rundmail', name: 'admin_tools_mailing')]
    public function mailing(Breadcrumb $breadcrumb): Response
    {
        return $this->render('@FerienpassAdmin/page/tools/mailing.html.twig', [
            'breadcrumb' => $breadcrumb->generate(['tools.title', ['route' => 'admin_tools']], 'mailing.title'),
        ]);
    }

    #[Route('/betroffenenrechte', name: 'admin_tools_subjectrights')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function subjectRights(Breadcrumb $breadcrumb): Response
    {
        return $this->render('@FerienpassAdmin/page/tools/noop.html.twig', [
            'breadcrumb' => $breadcrumb->generate(['tools.title', ['route' => 'admin_tools']], 'subjectrichts.title'),
        ]);
    }

    #[Route('/einstellungen', name: 'admin_tools_settings')]
    #[IsGranted('ROLE_SUPER_ADMIN')]
    public function settings(Breadcrumb $breadcrumb): Response
    {
        return $this->render('@FerienpassAdmin/page/tools/settings.html.twig', [
            'breadcrumb' => $breadcrumb->generate(['tools.title', ['route' => 'admin_tools']], 'settings.title', 'offerCategories.title'),
        ]);
    }

    #[IsGranted('ROLE_SUPER_ADMIN')]
    #[Route('/daten-löschen', name: 'admin_erase_data')]
    public function eraseData(EraseDataFacade $eraseDataFacade, Breadcrumb $breadcrumb, Request $request): Response
    {
        $participants = $eraseDataFacade->expiredParticipants();

        $form = $this->createFormBuilder()->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $eraseDataFacade->eraseData();

            return $this->redirectToRoute('admin_erase_data');
        }

        return $this->render('@FerienpassAdmin/page/tools/erase_data.html.twig', [
            'form' => $form,
            'participants' => $participants,
            'breadcrumb' => $breadcrumb->generate(['tools.title', ['route' => 'admin_tools']], 'eraseData.title'),
        ]);
    }

    #[Route('/download/{file}', name: 'admin_download')]
    public function download(string $file, UriSigner $uriSigner, Request $request): BinaryFileResponse
    {
        if (!$uriSigner->checkRequest($request)) {
            // throw $this->createNotFoundException();
        }

        return $this->file(base64_decode($file, true));
    }
}
