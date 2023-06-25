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

class ChartAttendancesController extends AbstractController
{
    public function __construct(private AttendanceRepository $attendanceRepository)
    {
    }

    public function __invoke(Edition $edition): Response
    {
        return $this->render('@FerienpassAdmin/fragment/statistics/chart_attendances.html.twig', [
            'data' => $this->getData($edition->getId()),
        ]);
    }

    private function getData(int $passEdition): array
    {
        $daysAndCount = $this->attendanceRepository->createQueryBuilder('a')
            ->select("DATE_FORMAT(a.createdAt, '%Y-%m-%d') AS day")
            ->addSelect('COUNT(a.id) as count')
            ->innerJoin('a.offer', 'o')
            ->where('o.edition = :edition')
            ->setParameter('edition', $passEdition)
            ->andWhere('a.status <> :status')
            ->setParameter('status', Attendance::STATUS_WITHDRAWN)
            ->groupBy('day')
            ->orderBy('day')
            ->getQuery()
            ->getScalarResult()
        ;

        if ([] === $daysAndCount) {
            return [];
        }

        // Transform array to key=>value structure
        $daysAndCount = array_combine(array_column($daysAndCount, 'day'), array_column($daysAndCount, 'count'));

        try {
            $days = array_keys($daysAndCount);
            $begin = new \DateTime($days[0]);
            $end = new \DateTime($days[\count($days) - 1]);
            $interval = \DateInterval::createFromDateString('1 day');
        } catch (\Exception) {
            return [];
        }

        $return = [];
        $return[(clone $begin)->modify('-1 day')->getTimestamp()] = 0;

        /** @var \DateTimeInterface $dt */
        foreach (new \DatePeriod($begin, $interval, $end) as $dt) {
            $count = $daysAndCount[$dt->format('Y-m-d')] ?? 0;

            $return[$dt->getTimestamp()] = (int) $count;
        }

        return $return;
    }
}
