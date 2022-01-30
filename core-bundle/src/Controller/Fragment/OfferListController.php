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
use Ferienpass\CoreBundle\Filter\OfferListFilter;
use Ferienpass\CoreBundle\Form\OfferFiltersType;
use Ferienpass\CoreBundle\Pagination\Paginator;
use Ferienpass\CoreBundle\Repository\EditionRepository;
use Ferienpass\CoreBundle\Repository\OfferRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

final class OfferListController extends AbstractController
{
    public function __construct(private EditionRepository $editionRepository, private OfferRepository $offerRepository)
    {
    }

    public function __invoke(Request $request, Session $session): Response
    {
        $qb = $this->offerRepository->createQueryBuilder('o')
            ->where('o.published = 1')
        ;

        $hasEditions = $this->editionRepository->count([]) > 0;
        $edition = $this->editionRepository->findOneToShow(PageModel::findByPk($request->attributes->get('pageModel')));

        if ($hasEditions && null !== $edition) {
            $qb->andWhere('o.edition = :edition')->setParameter('edition', $edition->getId(), Types::INTEGER);
        }

        if ($hasEditions && null === $edition) {
            return $this->render('@FerienpassCore/Fragment/offer_list.html.twig');
        }

        $qb->leftJoin('o.dates', 'dates');

        $filter = (new OfferListFilter($this->createForm(OfferFiltersType::class), $session))->filter($request->query->all(), $qb);

        $qb->orderBy('dates.begin');

        $paginator = (new Paginator($qb))->paginate($request->query->getInt('page', 1));

        return $this->render('@FerienpassCore/Fragment/offer_list.html.twig', [
            'edition' => $edition ?? null,
            'filter' => $filter,
            'pagination' => $paginator,
        ]);
    }
}
