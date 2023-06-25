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

use Ferienpass\CoreBundle\Applications\UnconfirmedApplications;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class SendAcceptancesController extends AbstractController
{
    public function __construct(private UnconfirmedApplications $unconfirmedApplications)
    {
    }

    public function __invoke(): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        // TODO: Only show widget when there is a current Edition with active/finished "allocation" task
        // TODO 2: Make the "allocation" task mandatory for Editions with lot application system
        $count = \count($this->unconfirmedApplications->getUninformedMembers()) + \count($this->unconfirmedApplications->getUninformedParticipants());
        if (!$count) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        return $this->render('@FerienpassAdmin/fragment/dashboard/send_acceptances.html.twig', [
            'count' => $count,
        ]);
    }
}
