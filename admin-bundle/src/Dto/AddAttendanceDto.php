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

namespace Ferienpass\AdminBundle\Dto;

use Ferienpass\CoreBundle\Entity\Attendance;
use Ferienpass\CoreBundle\Entity\Offer;
use Ferienpass\CoreBundle\Entity\Participant;

class AddAttendanceDto
{
    public ?Offer $offer;
    public ?Participant $participant;
    public string $status = Attendance::STATUS_WAITING;
    public bool $notify = false;

    public function __construct(Participant $participant = null, Offer $offer = null)
    {
        $this->participant = $participant;
        $this->offer = $offer;
    }

    public function getOffer(): ?Offer
    {
        return $this->offer;
    }

    public function getParticipant(): ?Participant
    {
        return $this->participant;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function shallNotify(): bool
    {
        return $this->notify;
    }
}
