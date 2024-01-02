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
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class ToolsController extends AbstractController
{
    #[Route('/tools', name: 'admin_tools')]
    public function index(Request $request, Breadcrumb $breadcrumb): Response
    {
        return $this->render('@FerienpassAdmin/page/tools/index.html.twig', [
            'breadcrumb' => $breadcrumb->generate('Werkzeuge & Einstellungen'),
        ]);
    }

    #[Route('/rundmail', name: 'admin_tools_mailing')]
    public function mailing(Request $request, Breadcrumb $breadcrumb): Response
    {
        return $this->render('@FerienpassAdmin/page/tools/noop.html.twig');
    }

    #[Route('/betroffenenrechte', name: 'admin_tools_subjectrights')]
    public function subjectRights(Request $request, Breadcrumb $breadcrumb): Response
    {
        return $this->render('@FerienpassAdmin/page/tools/noop.html.twig');
    }

    #[Route('/einstellungen', name: 'admin_tools_settings')]
    public function settings(Request $request, Breadcrumb $breadcrumb): Response
    {
        return $this->render('@FerienpassAdmin/page/tools/noop.html.twig');
    }
}
