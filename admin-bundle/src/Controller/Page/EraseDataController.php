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
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/daten-löschen')]
final class EraseDataController extends AbstractController
{
    #[Route('', name: 'admin_erase_data')]
    public function index(EraseDataFacade $eraseDataFacade, Breadcrumb $breadcrumb, Request $request, FormFactoryInterface $formFactory): Response
    {
        $participants = $eraseDataFacade->expiredParticipants();

        $form = $formFactory->createBuilder()->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $eraseDataFacade->eraseData();

            return $this->redirectToRoute('admin_erase_data');
        }

        return $this->render('@FerienpassAdmin/page/tools/erase_data.html.twig', [
            'form' => $form,
            'participants' => $participants,
            'breadcrumb' => $breadcrumb->generate('Tools & Werkzeuge', 'Daten löschen'),
        ]);
    }
}
