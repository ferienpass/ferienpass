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

namespace Ferienpass\AdminBundle\Controller\Dashboard;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class HeatmapController extends AbstractController
{
    public function __construct(private Connection $connection)
    {
    }

    public function __invoke(): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        return $this->render('@FerienpassAdmin/fragment/dashboard/attendances_heatmap.html.twig', [
            'values' => $this->countAttendancesByDay(),
        ]);
    }

    private function countOffersByDay(): array
    {
        $days = $this->connection
            ->executeQuery(
                <<<'SQL'
SELECT DATE_FORMAT(d.begin, '%Y-%m-%d') AS day, COUNT(*)
FROM OfferDate d
WHERE d.begin > (NOW() - INTERVAL 1 YEAR)
GROUP BY day
ORDER BY day
SQL
                ,
            )
            ->fetchAllNumeric();

        $return = [];
        foreach ($days as $row) {
            $ymd = $row[0];
            $return[$ymd] = (int) $row[1];
        }

        return $return;
    }

    private function countAttendancesByDay(): array
    {
        $days = $this->connection
            ->executeQuery(
                <<<'SQL'
SELECT DATE_FORMAT(a.createdAt, '%Y-%m-%d') AS day, COUNT(*)
FROM Attendance a
WHERE a.createdAt > (NOW() - INTERVAL 1 YEAR)
GROUP BY day
ORDER BY day
SQL
            )
            ->fetchAllNumeric();

        $return = [];
        foreach ($days as $row) {
            $ymd = $row[0];
            $ymd = strtotime($ymd);
            $return[$ymd] = (int) $row[1];
        }

        return $return;
    }
}
