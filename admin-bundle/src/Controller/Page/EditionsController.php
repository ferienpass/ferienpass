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
use Ferienpass\CoreBundle\Entity\Edition;
use Ferienpass\CoreBundle\Repository\EditionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

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
    public function index(Breadcrumb $breadcrumb, EditionRepository $repository): Response
    {
        $qb = $repository->createQueryBuilder('i');

        $qb->addOrderBy('i.archived', 'ASC');
        $qb->addOrderBy('i.name', 'ASC');

        return $this->render('@FerienpassAdmin/page/edition/index.html.twig', [
            'qb' => $qb,
            'createUrl' => $this->generateUrl('admin_editions_create'),
            'searchable' => ['name'],
            'breadcrumb' => $breadcrumb->generate(['tools.title', ['route' => 'admin_tools']], 'editions.title'),
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
