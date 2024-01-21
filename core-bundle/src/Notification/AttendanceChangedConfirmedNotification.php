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

class AttendanceChangedConfirmedNotification extends Notification
{
    private Attendance $attendance;

    public function getChannels(RecipientInterface $recipient): array
    {
        return ['email', 'sms'];
    }

    // $tokens = [];
    //
    //        $tokens['offer'] = $offer->getId();
    //        $tokens['participant'] = $participant->getId();
    //
    //        $tokens['footer_reason'] = $this->translator->trans('email.reason.applied', [], null, $language);
    //        $tokens['copyright'] = $this->translator->trans('email.copyright', [], null, $language);
    //        $tokens['attachment'] = $this->iCal->generate([$offer]);
    //
    //        $tokens['link'] = $this->router->generate('applications', [], UrlGeneratorInterface::ABSOLUTE_URL);

    public function attendance(Attendance $attendance): static
    {
        $this->attendance = $attendance;

        return $this;
    }
}
