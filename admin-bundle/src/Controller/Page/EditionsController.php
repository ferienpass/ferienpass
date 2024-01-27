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
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Translation\TranslatableMessage;

#[IsGranted('ROLE_ADMIN')]
#[Route('/saisons')]
final class EditionsController extends AbstractController
{
    private readonly array $stats;

    public function __construct(private readonly EditionRepository $editionRepository, #[TaggedIterator('ferienpass_admin.stats_widget')] iterable $stats)
    {
        $this->stats = $stats instanceof \Traversable ? iterator_to_array($stats) : $stats;
    }

    #[Route('', name: 'admin_editions_index')]
    public function index(Request $request, Breadcrumb $breadcrumb): Response
    {
        $items = $this->editionRepository->findBy([], ['archived' => 'ASC', 'name' => 'ASC']);

        return $this->render('@FerienpassAdmin/page/edition/index.html.twig', [
            'items' => $items,
            'breadcrumb' => $breadcrumb->generate(['tools.title', ['route' => 'admin_tools']], 'editions.title'),
        ]);
    }

    #[Route('/neu', name: 'admin_editions_create')]
    #[Route('/{alias}', name: 'admin_editions_edit')]
    public function edit(?Edition $edition, Request $request, EntityManagerInterface $em, Breadcrumb $breadcrumb, Flash $flash): Response
    {
        $form = $this->createForm(EditEditionType::class, $edition ?? new Host());

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
            'form' => $form->createView(),
            'breadcrumb' => $breadcrumb->generate(['tools.title', ['route' => 'admin_tools']], ['editions.title', ['route' => 'admin_editions_index']], $breadcrumbTitle),
        ]);
    }

    #[Route('/{alias}/statistik', name: 'admin_editions_stats')]
    public function stats(Edition $edition, Breadcrumb $breadcrumb): Response
    {
        return $this->render('@FerienpassAdmin/page/edition/stats.html.twig', [
            'edition' => $edition,
            'widgets' => array_map(fn (object $controller) => $controller::class, $this->stats),
            'breadcrumb' => $breadcrumb->generate(['tools.title', ['route' => 'admin_tools']], ['editions.title', ['route' => 'admin_editions_index']], [$edition->getName(), ['route' => 'admin_editions_edit', 'routeParameters' => ['alias' => $edition->getAlias()]]], 'editions.stats'),
        ]);
    }
}
