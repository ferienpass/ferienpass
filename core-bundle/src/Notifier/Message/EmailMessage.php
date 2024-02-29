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

namespace Ferienpass\CoreBundle\Notifier\Message;

use Ferienpass\CoreBundle\Notification\NotificationInterface;
use Ferienpass\CoreBundle\Notifier\Mime\NotificationEmail;
use Symfony\Component\Notifier\Exception\InvalidArgumentException;
use Symfony\Component\Notifier\Message\EmailMessage as BaseEmailMessage;
use Symfony\Component\Notifier\Message\FromNotificationInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\Recipient\EmailRecipientInterface;

class EmailMessage extends BaseEmailMessage implements FromNotificationInterface
{
    private ?Notification $notification = null;

    public static function fromFerienpassNotification(NotificationInterface&Notification $notification, EmailRecipientInterface $recipient, callable $useEmail = null): BaseEmailMessage
    {
        if ('' === $recipient->getEmail()) {
            throw new InvalidArgumentException(sprintf('"%s" needs an email, it cannot be empty.', __CLASS__));
        }

        $email = (new NotificationEmail($notification::getName()))
            ->to($recipient->getEmail())
            ->subject($notification->getSubject())
            ->content($notification->getContent())
            ->context($notification->getContext())
            ->messageId($notification->getMessageId())
        ;

        if (\is_callable($useEmail)) {
            $useEmail($email);
        }

        $message = new self($email);
        $message->notification = $notification;

        return $message;
    }

    public function getNotification(): ?Notification
    {
        return $this->notification;
    }
}
