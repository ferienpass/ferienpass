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

use Ferienpass\CoreBundle\Entity\Edition;
use Ferienpass\CoreBundle\Repository\EditionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/saisons')]
final class EditionController extends AbstractController
{
    private array $stats;

    public function __construct(private EditionRepository $editionRepository, iterable $stats)
    {
        $this->stats = $stats instanceof \Traversable ? iterator_to_array($stats) : $stats;
    }

    #[Route('/', name: 'admin_editions_index')]
    public function index(Request $request): Response
    {
        $items = $this->editionRepository->findAll();

        return $this->render('@FerienpassAdmin/page/edition/index.html.twig', [
            'items' => $items,
        ]);
    }

    #[Route('/{alias}/statistik', name: 'admin_edition_stats')]
    public function stats(Edition $edition, Request $request): Response
    {
        return $this->render('@FerienpassAdmin/page/edition/stats.html.twig', [
            'edition' => $edition,
            'widgets' => array_map(fn (object $controller) => $controller::class, $this->stats),
        ]);
    }
}
