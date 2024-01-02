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

use Ferienpass\AdminBundle\Breadcrumb\Breadcrumb;
use Ferienpass\CoreBundle\Notifier;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[Route('/gesendete-nachrichten')]
final class NotificationsOutboxController extends AbstractController
{
    #[Route('', name: 'admin_notifications_outbox')]
    public function index(Notifier $notifier, Breadcrumb $breadcrumb): Response
    {
        return $this->render('@FerienpassAdmin/page/notifications/outbox.html.twig', [
            'breadcrumb' => $breadcrumb->generate('Benachrichtigungen', 'Gesendete Nachrichten'),
        ]);
    }
}
