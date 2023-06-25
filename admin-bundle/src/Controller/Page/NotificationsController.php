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

use Doctrine\Persistence\ManagerRegistry;
use Ferienpass\AdminBundle\Breadcrumb\Breadcrumb;
use Ferienpass\AdminBundle\Form\NotificationType;
use Ferienpass\CoreBundle\Applications\UnconfirmedApplications;
use Ferienpass\CoreBundle\Entity\Notification;
use Ferienpass\CoreBundle\Message\ConfirmApplications;
use Ferienpass\CoreBundle\Notifier;
use Ferienpass\CoreBundle\Repository\NotificationRepository;
use Ferienpass\CoreBundle\Ux\Flash;
use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Translation\TranslatableMessage;

#[IsGranted('ROLE_ADMIN')]
#[Route('/benachrichtigungen')]
final class NotificationsController extends AbstractController
{
    public function __construct(private Notifier $notifier, private FormFactoryInterface $formFactory, private ManagerRegistry $doctrine, private FactoryInterface $menuFactory)
    {
    }

    #[Route('{type}', name: 'admin_notifications', defaults: ['type' => ''])]
    public function index(string $type, NotificationRepository $notificationRepository, Request $request): Response
    {
        if ('' === $type) {
            return $this->redirectToRoute($request->get('_route'), ['type' => $this->notifier->getNotificationNames()[0]]);
        }

        if (!$this->notifier->has($type)) {
            throw new NotFoundHttpException('');
        }

        $notification = $notificationRepository->findOneBy(['type' => $type]);

        return $this->render('@FerienpassAdmin/page/notifications/index.html.twig', [
            'notification' => $notification,
            'aside_headline' => 'Benachrichtigungen',
            'aside_nav' => $this->getMenu(),
        ]);
    }

    #[Route('{type}/bearbeiten', name: 'admin_notification_edit', defaults: ['type' => ''])]
    public function edit(string $type, NotificationRepository $notificationRepository, Request $request)
    {
        $em = $this->doctrine->getManager();

        $notification = $notificationRepository->findOneBy(['type' => $type]);
        if (null === $notification) {
            $notification = new Notification($type);
        }

        $form = $this->formFactory->create(NotificationType::class, $notification);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Notification $notification */
            $notification = $form->getData();

            $em->persist($notification);
            $em->flush();

            $this->addFlash(...Flash::confirmationModal()
                ->headline(new TranslatableMessage('notifications.confirmSubmit.headline'))
                ->text(new TranslatableMessage('notifications.confirmSubmit.text'))
                ->dismissable()
                ->create()
            );

            return $this->redirectToRoute('admin_notifications', $request->get('_route_params'));
        }

        return $this->render('@FerienpassAdmin/page/notifications/edit.html.twig', [
            'form' => $form,
            'notification' => $notification,
            'aside_headline' => 'notifications.headline',
            'aside_nav' => $this->getMenu(),
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
