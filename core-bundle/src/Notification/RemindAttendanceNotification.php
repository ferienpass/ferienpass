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
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\Recipient\RecipientInterface;

class RemindAttendanceNotification extends Notification
{
    private Attendance $attendance;

    public function getChannels(RecipientInterface $recipient): array
    {
        return ['email'];
    }

    public function attendance(Attendance $attendance): static
    {
        $this->attendance = $attendance;

        return $this;
    }
}
