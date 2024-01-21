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
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;

#[IsGranted('ROLE_ADMIN')]
#[Route('/saisons')]
#[AsLiveComponent(name: 'Admin:EditionsEdit', template: '@FerienpassAdmin/components/EditOffer.html.twig')]
final class EditionsEditController extends AbstractController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;

    #[LiveProp]
    public Edition $initialFormData;

    public function __construct(private readonly FormFactoryInterface $formFactory)
    {
    }

    #[Route('/neu', name: 'admin_editions_create')]
    #[Route('/{alias}', name: 'admin_editions_edit')]
    public function edit(?Edition $edition, Request $request, EntityManagerInterface $em, Breadcrumb $breadcrumb, Flash $flash): Response
    {
        $this->initialFormData = $edition ?? new Edition();

        $form = $this->instantiateForm();
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

        return $this->render('@FerienpassAdmin/page/edition/edit.html.twig', [
            'item' => $edition,
            'form' => $form,
            'breadcrumb' => $breadcrumb->generate(['Werkzeuge & Einstellungen', ['route' => 'admin_tools']], ['editions.title', ['route' => 'admin_editions_index']], $breadcrumbTitle),
        ]);
    }

    protected function instantiateForm(): FormInterface
    {
        return $this->formFactory->create(EditEditionType::class, $this->initialFormData);
    }
}
