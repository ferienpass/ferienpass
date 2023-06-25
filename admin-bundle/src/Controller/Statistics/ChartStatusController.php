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
use Symfony\Contracts\Translation\TranslatorInterface;

class ChartStatusController extends AbstractController
{
    public function __construct(private AttendanceRepository $attendanceRepository, private TranslatorInterface $translator)
    {
    }

    public function __invoke(Edition $edition): Response
    {
        return $this->render('@FerienpassAdmin/fragment/statistics/chart_status.html.twig', [
            'data' => $this->getData($edition->getId()),
        ]);
    }

    private function getData(int $passEdition): array
    {
        $statusAndCount = $this->attendanceRepository->createQueryBuilder('a')
            ->select('a.status')
            ->addSelect('COUNT(a.id) as count')
            ->innerJoin('a.offer', 'o')
            ->where('o.edition = :edition')
            ->setParameter('edition', $passEdition)
            ->groupBy('a.status')
            ->getQuery()
            ->getScalarResult()
        ;

        // Transform array to key=>value structure
        $statusAndCount = array_combine(array_column($statusAndCount, 'status'), array_column($statusAndCount, 'count'));

        $return = [];

        foreach ([Attendance::STATUS_CONFIRMED, Attendance::STATUS_WAITLISTED, Attendance::STATUS_ERROR, Attendance::STATUS_WAITING, Attendance::STATUS_WITHDRAWN] as $status) {
            $return[] = [
                'title' => $this->translator->trans('MSC.attendance_status.'.$status, [], 'contao_default'),
                'count' => (int) ($statusAndCount[$status] ?? 0),
            ];
        }

        return $return;
    }
}
