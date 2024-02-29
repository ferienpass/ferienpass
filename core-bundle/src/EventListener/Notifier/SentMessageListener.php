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

namespace Ferienpass\CoreBundle\EventListener\Notifier;

use Doctrine\ORM\EntityManagerInterface;
use Ferienpass\CoreBundle\Notifier\Message\EmailMessage;
use Ferienpass\CoreBundle\Notifier\Mime\NotificationEmail;
use Ferienpass\CoreBundle\Repository\MessageLogRepository;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
class SentMessageListener
{
    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly MessageLogRepository $repository)
    {
    }

    public function __invoke(\Symfony\Component\Mailer\Event\SentMessageEvent $event)
    {
        $message = $event->getMessage()->getOriginalMessage();
        if (!$message instanceof NotificationEmail) {
            return;
        }
        $a = $this->repository->findOneBy(['uniqueId' => $message->getMessageId()]);
        dd($a);

        //        $log = new NotificationLog();

        if ($message instanceof EmailMessage) {
            $notification = $message->getNotification();

            //                    $logEntry = new NotificationLog('', $message, $message->g);
            //
            //                    $this->entityManager->persist($logEntry);
            //
            //                    $this->entityManager->flush();
        }
    }
}
