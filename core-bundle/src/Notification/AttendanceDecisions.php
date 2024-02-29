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

namespace Ferienpass\CoreBundle\Notification;

use Ferienpass\CoreBundle\Entity\Attendance;
use Ferienpass\CoreBundle\Notifier\Message\EmailMessage;
use Symfony\Component\Notifier\Message\EmailMessage as SymfonyEmailMessage;
use Symfony\Component\Notifier\Notification\EmailNotificationInterface;
use Symfony\Component\Notifier\Recipient\EmailRecipientInterface;
use Symfony\Component\Notifier\Recipient\RecipientInterface;

class AttendanceDecisions extends AbstractNotification implements NotificationInterface, EmailNotificationInterface
{
    /** @var array<int, array<int, Attendance>> */
    private array $attendances;

    public static function getName(): string
    {
        return 'attendance_decisions';
    }

    public function getChannels(RecipientInterface $recipient): array
    {
        return ['email'];
    }

    public function attendance(Attendance $attendance): static
    {
        $this->attendances[$attendance->getParticipant()->getId()][] = $attendance;

        return $this;
    }

    public function getContext(): array
    {
        return array_merge(parent::getContext(), [
            'attendances' => $this->attendances,
        ]);
    }

    public static function getAvailableTokens(): array
    {
        return array_merge(parent::getAvailableTokens(), ['attendances']);
    }

    public function asEmailMessage(EmailRecipientInterface $recipient, string $transport = null): ?SymfonyEmailMessage
    {
        return EmailMessage::fromFerienpassNotification($this, $recipient);
    }
}
