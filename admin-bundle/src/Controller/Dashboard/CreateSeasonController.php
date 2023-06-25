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

class CreateSeasonController extends AbstractController
{
    public function __construct(private Connection $connection)
    {
    }

    public function __invoke(): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        $hasEdition = 0 !== $this->connection
                ->createQueryBuilder()
                ->select('DISTINCT e.id')
                ->from('Edition', 'e')
                ->innerJoin('e', 'EditionTask', 't', 't.pid = e.id')
                ->where('t.periodEnd > NOW()')
                ->executeQuery()
                ->rowCount()
        ;

        if ($hasEdition) {
            return new Response();
        }

        return $this->render('@FerienpassAdmin/fragment/dashboard/create_season.html.twig');
    }
}
