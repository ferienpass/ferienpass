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

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Ferienpass\AdminBundle\Breadcrumb\Breadcrumb;
use Ferienpass\AdminBundle\Form\EditNotificationType;
use Ferienpass\CoreBundle\Applications\UnconfirmedApplications;
use Ferienpass\CoreBundle\Entity\Notification;
use Ferienpass\CoreBundle\Message\ConfirmApplications;
use Ferienpass\CoreBundle\Notifier;
use Ferienpass\CoreBundle\Repository\NotificationRepository;
use Ferienpass\CoreBundle\Session\Flash;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Translation\TranslatableMessage;

#[IsGranted('ROLE_ADMIN')]
#[Route('/benachrichtigungen')]
final class NotificationsController extends AbstractController
{
    public function __construct(private readonly Notifier $notifier, private readonly FormFactoryInterface $formFactory, private readonly ManagerRegistry $doctrine, private readonly FactoryInterface $menuFactory)
    {
    }

    #[Route('', name: 'admin_notifications_index')]
    public function index(Notifier $notifier, Breadcrumb $breadcrumb): Response
    {
        return $this->render('@FerienpassAdmin/page/tools/noop.html.twig');

        return $this->render('@FerienpassAdmin/page/notifications/index.html.twig', [
            'notifications' => $notifier->getNotificationNames(),
            'breadcrumb' => $breadcrumb->generate('Benachrichtigungen'),
        ]);
    }

    #[Route('/{type}', name: 'admin_notifications_show')]
    public function show(string $type, NotificationRepository $notificationRepository, Breadcrumb $breadcrumb): Response
    {
        if (!$this->notifier->has($type)) {
            throw new NotFoundHttpException('');
        }

        $notification = $notificationRepository->findOneBy(['type' => $type]);

        return $this->render('@FerienpassAdmin/page/notifications/show.html.twig', [
            'notification' => $notification,
            'breadcrumb' => $breadcrumb->generate('Benachrichtigungen', 'notifications.'.$type.'.0'),
        ]);
    }

    #[Route('/{type}/bearbeiten', name: 'admin_notifications_edit')]
    public function edit(string $type, NotificationRepository $notificationRepository, Notifier $notifier, Request $request, EntityManagerInterface $em, Flash $flash, Breadcrumb $breadcrumb)
    {
        $notification = $notificationRepository->findOneBy(['type' => $type]);
        if (null === $notification) {
            $notification = new Notification($type);
        }

        $form = $this->formFactory->create(EditNotificationType::class, $notification);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if (!$em->contains($notification)) {
                $em->persist($notification);

                $flash->addConfirmationModal('Benachrichtigung erstellt', 'Die Benachrichtigung wurde erstellt und ist ab sofort aktiv.', dismissable: true);
            } else {
                $flash->addConfirmation(text: 'Die Änderungen würden gespeichert.');
            }

            $em->flush();

            return $this->redirectToRoute('admin_notifications_show', ['type' => $type]);
        }

        return $this->render('@FerienpassAdmin/page/notifications/edit.html.twig', [
            'form' => $form,
            'notification' => $notification,
            'headline' => 'notifications.'.$type.'.0',
            'breadcrumb' => $breadcrumb->generate('Benachrichtigungen', 'notifications.'.$type.'.0', 'Bearbeiten'),
        ]);
    }

    #[Route('/zusagen-versenden', name: 'admin_notifications_send_acceptances')]
    public function sendAcceptances(Request $request, MessageBusInterface $messageBus, UnconfirmedApplications $unconfirmedApplications, FormFactoryInterface $formFactory, Breadcrumb $breadcrumb)
    {
        $form = $formFactory->createBuilder()->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $messageBus->dispatch(new ConfirmApplications($unconfirmedApplications->getAttendanceIds()));

            return $this->redirectToRoute('admin_notifications_send_acceptances');
        }

        return $this->renderForm('@FerienpassAdmin/page/notifications/send_attendances.html.twig', [
            'members' => $unconfirmedApplications->getUninformedMembers(),
            'participants' => $unconfirmedApplications->getUninformedParticipants(),
            'form' => $form,
            'breadcrumb' => $breadcrumb->generate('Benachrichtigungen', 'Zusagen versenden'),
        ]);
    }

    private function getMenu(): ItemInterface
    {
        $menu = $this->menuFactory->createItem('root');

        foreach ($this->notifier->getNotificationNames() as $notification) {
            $menu->addChild(new TranslatableMessage('notification.'.$notification.'.0'), [
                'route' => 'admin_notifications',
                'routeParameters' => ['type' => $notification],
                'extras' => ['description' => new TranslatableMessage('notification.'.$notification.'.1')],
            ]);
        }

        return $menu;
    }
}
