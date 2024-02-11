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
use Ferienpass\AdminBundle\Breadcrumb\Breadcrumb;
use Ferienpass\AdminBundle\Form\EditNotificationType;
use Ferienpass\CoreBundle\Applications\UnconfirmedApplications;
use Ferienpass\CoreBundle\Entity\Edition;
use Ferienpass\CoreBundle\Entity\Notification;
use Ferienpass\CoreBundle\Message\ConfirmApplications;
use Ferienpass\CoreBundle\Notification\AbstractNotification;
use Ferienpass\CoreBundle\Notification\EditionAwareNotificationInterface;
use Ferienpass\CoreBundle\Notifier;
use Ferienpass\CoreBundle\Repository\NotificationRepository;
use Ferienpass\CoreBundle\Session\Flash;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Notifier\Notification\EmailNotificationInterface;
use Symfony\Component\Notifier\Recipient\Recipient;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('ROLE_ADMIN')]
#[Route('/benachrichtigungen')]
final class NotificationsController extends AbstractController
{
    public function __construct(private readonly TranslatorInterface $translator, private readonly NormalizerInterface $normalizer)
    {
    }

    #[Route('/{type?}/{edition?}', name: 'admin_notifications')]
    #[Route('/{type?}/neu', name: 'admin_notifications_new', priority: 2)]
    public function index(?string $type, #[MapEntity(mapping: ['edition' => 'alias'])] ?Edition $edition, Request $request, Notifier $notifier, NotificationRepository $repository, Breadcrumb $breadcrumb, EntityManagerInterface $em, Flash $flash): Response
    {
        if (null === $type) {
            return $this->render('@FerienpassAdmin/page/notifications/index.html.twig', [
                'types' => $notifier->types(),
                'notifier' => $notifier,
                'breadcrumb' => $breadcrumb->generate(['tools.title', ['route' => 'admin_tools']], 'notifications.title'),
            ]);
        }

        if (!\in_array($type, $notifier->types(), true)) {
            throw $this->createNotFoundException();
        }

        if ($request->get('edition') && null === $edition) {
            throw $this->createNotFoundException();
        }

        $editions = $em->createQuery('SELECT e FROM '.Edition::class.' e WHERE e IN (SELECT IDENTITY(n.edition) FROM '.Notification::class.' n WHERE n.type = :type)')->setParameter('type', $type)->getResult();
        $entity = $repository->findOneBy(['type' => $type, 'edition' => $edition]) ?? $this->newNotificationForType($type);
        $form = $this->createForm(EditNotificationType::class, $entity, ['notification_type' => $type, 'supports_sms' => 'attendance_newly_confirmed' === $type, 'new_edition' => 'admin_notifications_new' === $request->get('_route'), 'can_delete' => null !== $edition]);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if (!$em->contains($entity)) {
                $em->persist($entity);
            }

            if ($form->has('delete') && ($button = $form->get('delete')) && $button instanceof SubmitButton && $button->isClicked()) {
                $em->remove($entity);
                $em->flush();

                $flash->addConfirmation(text: 'Die Benachrichtigung für die Saison wurde gelöscht.');

                return $this->redirectToRoute('admin_notifications', ['type' => $type]);
            }

            $flash->addConfirmation(text: 'Die Änderungen wurden gespeichert.');

            $em->flush();

            return $this->redirectToRoute('admin_notifications', ['type' => $type, 'edition' => $edition?->getAlias()]);
        }

        $availableTokens = [];
        if (is_subclass_of($notificationClass = $notifier->getClass($type), AbstractNotification::class)) {
            $tokenKeys = $notificationClass::getAvailableTokens();
        }

        foreach ($tokenKeys ?? [] as $token) {
            switch ($token) {
                case 'baseUrl':
                    $availableTokens[$token] = $request->getSchemeAndHttpHost().$request->getBaseUrl();
                    break;
                case 'user':
                case 'offer':
                case 'participant':
                case 'attendance':
                    $tokens = $this->normalizer->normalize($this->getUser(), context: ['groups' => 'notification']);
                    foreach (array_keys($tokens) as $property) {
                        $availableTokens["$token.$property"] = $this->container->get('twig')->createTemplate(sprintf('{{ %s }}', "$token.$property"))->render([$token => $tokens]);
                    }
                    break;
            }
        }

        $mockNotification = $notifier->createMock($type, $entity->getEmailSubject() ?? '', $entity->getEmailText() ?? '');
        $mockEmail = $mockNotification instanceof EmailNotificationInterface ? $mockNotification->asEmailMessage(new Recipient('example@example.org')) : null;

        return $this->render('@FerienpassAdmin/page/notifications/index.html.twig', [
            'form' => $form->createView(),
            'edition' => $edition,
            'editions' => $editions,
            'canEditEditions' => is_subclass_of($notificationClass, EditionAwareNotificationInterface::class),
            'availableTokens' => $availableTokens,
            'mockEmail' => $mockEmail,
            'types' => $notifier->types(),
            'notifier' => $notifier,
            'breadcrumb' => $breadcrumb->generate(['tools.title', ['route' => 'admin_tools']], ['notifications.title', ['route' => 'admin_notifications']], 'notifications.'.$type.'.0', $edition?->getName()),
        ]);
    }

    #[Route('/zusagen-versenden', name: 'admin_notifications_send_acceptances')]
    public function sendAcceptances(Request $request, MessageBusInterface $messageBus, UnconfirmedApplications $unconfirmedApplications, Breadcrumb $breadcrumb)
    {
        $form = $this->createFormBuilder()->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $messageBus->dispatch(new ConfirmApplications($unconfirmedApplications->getAttendanceIds()));

            return $this->redirectToRoute('admin_notifications_send_acceptances');
        }

        return $this->render('@FerienpassAdmin/page/notifications/send_attendances.html.twig', [
            'members' => $unconfirmedApplications->getUninformedMembers(),
            'participants' => $unconfirmedApplications->getUninformedParticipants(),
            'form' => $form->createView(),
            'breadcrumb' => $breadcrumb->generate('Benachrichtigungen', 'Zusagen versenden'),
        ]);
    }

    private function newNotificationForType(string $type): Notification
    {
        $notification = new Notification($type);

        $getTransKey = fn (string $transKey): string => sprintf('defaults.%s.%s', $type, $transKey);

        $transKey = $getTransKey('emailText');
        if ($transKey !== ($emailText = $this->translator->trans($transKey, domain: 'notifications'))) {
            $notification->setEmailText($emailText);
        }

        $transKey = $getTransKey('emailSubject');
        if ($transKey !== ($emailSubject = $this->translator->trans($transKey, domain: 'notifications'))) {
            $notification->setEmailSubject($emailSubject);
        }

        $transKey = $getTransKey('smsText');
        if ($transKey !== ($smsText = $this->translator->trans($transKey, domain: 'notifications'))) {
            $notification->setSmsText($smsText);
        }

        return $notification;
    }
}
