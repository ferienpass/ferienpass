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
use Ferienpass\CoreBundle\Entity\Offer\OfferInterface;
use Ferienpass\CoreBundle\Entity\Participant;

class AddAttendanceDto
{
    public ?OfferInterface $offer;
    public ?Participant $participant;
    public string $status;
    public bool $notify;

    public function __construct(Participant $participant = null, OfferInterface $offer = null, string $status = Attendance::STATUS_WAITING, bool $notify = false)
    {
        $this->participant = $participant;
        $this->offer = $offer;
        $this->status = $status;
        $this->notify = $notify;
    }

    public function getOffer(): ?OfferInterface
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
