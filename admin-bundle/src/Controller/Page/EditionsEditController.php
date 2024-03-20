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

use Doctrine\ORM\EntityManagerInterface;
use Ferienpass\AdminBundle\Breadcrumb\Breadcrumb;
use Ferienpass\AdminBundle\Form\EditEditionType;
use Ferienpass\CoreBundle\Entity\Edition;
use Ferienpass\CoreBundle\Session\Flash;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Translation\TranslatableMessage;

#[IsGranted('ROLE_ADMIN')]
#[Route('/saisons')]
final class EditionsEditController extends AbstractController
{
    #[Route('/neu', name: 'admin_editions_create')]
    #[Route('/{alias}', name: 'admin_editions_edit')]
    public function edit(?Edition $edition, Request $request, EntityManagerInterface $em, Breadcrumb $breadcrumb, Flash $flash): Response
    {
        $edition ??= new Edition();

        $form = $this->createForm(EditEditionType::class, $edition);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if (!$em->contains($edition = $form->getData())) {
                $em->persist($edition);
            }

            $em->flush();

            $flash->addConfirmation(text: new TranslatableMessage('editConfirm', domain: 'admin'));

            return $this->redirectToRoute('admin_editions_edit', ['alias' => $edition->getAlias()]);
        }

        $breadcrumbTitle = $edition ? $edition->getName().' (bearbeiten)' : 'editions.new';

        /** @noinspection FormViewTemplate `createView()` messes ups error handling/redirect */
        return $this->render('@FerienpassAdmin/page/edition/edit.html.twig', [
            'item' => $edition,
            'form' => $form,
            'breadcrumb' => $breadcrumb->generate(['tools.title', ['route' => 'admin_tools']], ['editions.title', ['route' => 'admin_editions_index']], $breadcrumbTitle),
        ]);
    }
}
