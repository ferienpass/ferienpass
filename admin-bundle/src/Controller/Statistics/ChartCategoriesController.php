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

namespace Ferienpass\AdminBundle\Controller\Statistics;

use Ferienpass\CoreBundle\Entity\Attendance;
use Ferienpass\CoreBundle\Entity\Edition;
use Ferienpass\CoreBundle\Repository\AttendanceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class ChartCategoriesController extends AbstractController
{
    public function __construct(private AttendanceRepository $attendanceRepository)
    {
    }

    public function __invoke(Edition $edition): Response
    {
        $data = $this->getData($edition->getId());
        if (empty($data)) {
            return new Response();
        }

        return $this->render('@FerienpassAdmin/fragment/statistics/chart_categories.html.twig', [
            'data' => $data,
        ]);
    }

    private function getData(int $passEdition): ?array
    {
        $qb = $this->attendanceRepository->createQueryBuilder('a')
            ->select('c.name AS category', 'COUNT(a.id) AS count')
            ->innerJoin('a.offer', 'o')
            ->innerJoin('o.categories', 'c')
            ->andWhere('o.edition = :edition')
            ->setParameter('edition', $passEdition)
            ->groupBy('c.id')
        ;
        $count = (clone $qb)
            ->andWhere('a.status <> :status')
            ->setParameter('status', Attendance::STATUS_WITHDRAWN)
            ->getQuery()
            ->getScalarResult()
        ;
        $countConfirmed = (clone $qb)
            ->andWhere('a.status = :status')
            ->setParameter('status', Attendance::STATUS_CONFIRMED)
            ->getQuery()
            ->getScalarResult()
        ;

        // Transform array to key=>value structure
        $countConfirmed = array_combine(array_column($countConfirmed, 'category'), array_column($countConfirmed, 'count'));

        $return = [];
        foreach ($count as $i => $v) {
            $return[] = [
                'category' => $v['category'],
                'count' => (int) $v['count'],
                'count_confirmed' => (int) $countConfirmed[$v['category']],
            ];
        }

        return $return;
    }
}
