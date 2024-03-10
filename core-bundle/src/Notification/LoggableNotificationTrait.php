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

use Ferienpass\CoreBundle\Entity\MessengerLog;

trait LoggableNotificationTrait
{
    protected ?MessengerLog $belongsTo = null;

    public function belongsTo(MessengerLog $messageLog): static
    {
        $this->belongsTo = $messageLog;

        return $this;
    }

    public function getBelongsTo(): ?MessengerLog
    {
        return $this->belongsTo;
    }
}
