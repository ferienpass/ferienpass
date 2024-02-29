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

use Doctrine\Common\Collections\Criteria;
use Ferienpass\AdminBundle\Breadcrumb\Breadcrumb;
use Ferienpass\CoreBundle\Message\ParticipantListChanged;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_SUPER_ADMIN')]
#[Route('/ereignisse')]
final class EventLogController extends AbstractController
{
    #[Route('', name: 'admin_event_log')]
    public function index(MessageLogRepository $repository, Breadcrumb $breadcrumb, Request $request): Response
    {
        $criteria = (new Criteria())
            ->where(Criteria::expr()->neq('message', ParticipantListChanged::class))
            ->orderBy(['createdAt' => 'DESC'])
        ;

        $events = $repository->matching($criteria);

        return $this->render('@FerienpassAdmin/page/tools/event_log.html.twig', [
            'events' => $events,
            'breadcrumb' => $breadcrumb->generate('tools.title', 'Ereignisse'),
        ]);
    }
}
