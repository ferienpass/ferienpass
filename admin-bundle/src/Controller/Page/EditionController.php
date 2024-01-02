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
use Ferienpass\CoreBundle\Entity\Host;
use Ferienpass\CoreBundle\Repository\EditionRepository;
use Ferienpass\CoreBundle\Session\Flash;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Translation\TranslatableMessage;

#[Route('/saisons')]
final class EditionController extends AbstractController
{
    private array $stats;

    public function __construct(private EditionRepository $editionRepository, iterable $stats)
    {
        $this->stats = $stats instanceof \Traversable ? iterator_to_array($stats) : $stats;
    }

    #[Route('', name: 'admin_editions_index')]
    public function index(Request $request, Breadcrumb $breadcrumb): Response
    {
        $items = $this->editionRepository->findAll();

        return $this->render('@FerienpassAdmin/page/edition/index.html.twig', [
            'items' => $items,
            'breadcrumb' => $breadcrumb->generate(['Werkzeuge & Einstellungen', ['route' => 'admin_tools']], 'Saisons konfigurieren'),
        ]);
    }

    #[Route('/neu', name: 'admin_editions_create')]
    #[Route('/{alias}', name: 'admin_editions_edit')]
    public function edit(?Edition $edition, Request $request, FormFactoryInterface $formFactory, EntityManagerInterface $em, Breadcrumb $breadcrumb, Flash $flash): Response
    {
        $form = $formFactory->create(EditEditionType::class, $edition ?? new Host());

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

        return $this->render('@FerienpassAdmin/page/hosts/edit.html.twig', [
            'item' => $edition,
            'form' => $form,
            'breadcrumb' => $breadcrumb->generate(['editions.title', ['route' => 'admin_editions_index']], $breadcrumbTitle),
        ]);
    }

    #[Route('/{alias}/statistik', name: 'admin_editions_stats')]
    public function stats(Edition $edition, Request $request): Response
    {
        return $this->render('@FerienpassAdmin/page/edition/stats.html.twig', [
            'edition' => $edition,
            'widgets' => array_map(fn (object $controller) => $controller::class, $this->stats),
        ]);
    }
}
