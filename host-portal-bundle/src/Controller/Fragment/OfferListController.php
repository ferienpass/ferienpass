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

namespace Ferienpass\HostPortalBundle\Controller\Fragment;

use Contao\FrontendUser;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Query\Expr\Join;
use Ferienpass\CoreBundle\Repository\EditionRepository;
use Ferienpass\CoreBundle\Repository\HostRepository;
use Ferienpass\CoreBundle\Repository\OfferRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class OfferListController extends AbstractFragmentController
{
    public function __construct(private EditionRepository $editionRepository, private HostRepository $hostRepository, private OfferRepository $offerRepository)
    {
    }

    public function __invoke(Request $request): Response
    {
        $edition = null;
        $user = $this->container->get('contao.framework')->createInstance(FrontendUser::class);
        if (!$user->id) {
            return new Response();
        }

        $qb = $this->offerRepository->createQueryBuilder('o');

        if ($request->query->has('host')) {
            $host = $this->hostRepository->find($request->query->get('host'));
            $this->denyAccessUnlessGranted('view', $host);

            $qb->andWhere(':host MEMBER OF o.hosts')->setParameter('host', $host);
        } else {
            $hosts = $this->hostRepository->findByMemberId((int) $user->id);
            $qb
                ->innerJoin('o.hosts', 'h', Join::WITH, 'h IN (:hosts)')
                ->setParameter('hosts', $hosts)
            ;
        }

        $edition = null;
        if ($request->query->has('edition')) {
            $edition = $this->editionRepository->findOneBy(['alias' => $request->query->get('edition')]);
        }

        if (null === $edition) {
            $edition = $this->editionRepository->findDefaultForHost();
        }

        if (null !== $edition) {
            $qb
                ->andWhere('o.edition = :edition')
                ->setParameter('edition', $edition->getId(), Types::INTEGER)
            ;
        }

        $offers = $qb
            ->leftJoin('o.dates', 'd')
            ->orderBy('d.begin')
            ->getQuery()
            ->getResult()
        ;

        return $this->render('@FerienpassHostPortal/fragment/offer_list.html.twig', [
            'edition' => $edition,
            'offers' => $offers,
        ]);
    }
}
