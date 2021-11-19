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

namespace Ferienpass\CoreBundle\Controller\Fragment;

use Contao\CoreBundle\Controller\AbstractController;
use Contao\PageModel;
use Doctrine\DBAL\Types\Types;
use Ferienpass\CoreBundle\Form\Filter\OfferFilters;
use Ferienpass\CoreBundle\Pagination\Paginator;
use Ferienpass\CoreBundle\Repository\EditionRepository;
use Ferienpass\CoreBundle\Repository\OfferRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

final class OfferListController extends AbstractController
{
    private EditionRepository $editionRepository;
    private OfferRepository $offerRepository;
    private OfferFilters $offerFilters;

    public function __construct(EditionRepository $passEditionRepository, OfferRepository $offerRepository, OfferFilters $offerFilters)
    {
        $this->editionRepository = $passEditionRepository;
        $this->offerRepository = $offerRepository;
        $this->offerFilters = $offerFilters;
    }

    public function __invoke(Request $request, Session $session): Response
    {
        $qb = $this->offerRepository->createQueryBuilder('o')
            ->andWhere('o.published = 1')
        ;

        $hasEditions = $this->editionRepository->count([]) > 0;
        $edition = $this->editionRepository->findOneToShow(PageModel::findByPk($request->get('pageModel')));

        if ($hasEditions && null !== $edition) {
            $qb->andWhere('o.edition = :edition')->setParameter('edition', $edition->getId(), Types::INTEGER);
        }

        if ($hasEditions && null === $edition) {
            return $this->render('@FerienpassCore/Fragment/offer_list.html.twig');
        }

        $qb
            ->leftJoin('o.dates', 'dates')
            ->addGroupBy('o.id')
            ->orderBy('MIN(dates.begin)')
        ;

        // Only show offers in future or currently running
        $now = new \DateTimeImmutable();
        $qb
            ->andWhere($qb->expr()->orX()->add('dates IS NULL')->add($qb->expr()->orX('dates.begin >= :now', 'dates.end >= :now')))
            ->setParameter('now', $now, Types::DATETIME_MUTABLE)
        ;

        $filtersForm = $this->offerFilters->createForm();

        $filtersForm->handleRequest($request, $qb);

        $paginator = (new Paginator($qb))->paginate($request->query->getInt('page', 1));

        return $this->render('@FerienpassCore/Fragment/offer_list.html.twig', [
            'edition' => $edition ?? null,
            'filters' => $filtersForm->createView(),
            'pagination' => $paginator,
        ]);
    }
}
