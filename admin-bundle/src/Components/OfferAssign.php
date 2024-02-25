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

namespace Ferienpass\AdminBundle\Components;

use Doctrine\ORM\EntityManagerInterface;
use Ferienpass\CoreBundle\Entity\Attendance;
use Ferienpass\CoreBundle\Entity\Offer;
use Ferienpass\CoreBundle\Message\AttendanceStatusChanged;
use Ferienpass\CoreBundle\Message\ParticipantListChanged;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
class OfferAssign extends AbstractController
{
    use DefaultActionTrait;

    #[LiveProp]
    public Offer $offer;

    #[LiveProp(writable: true)]
    public bool $autoAssign;

    public function __construct()
    {
        $this->autoAssign = false;
    }

    #[LiveListener('statusChanged')]
    public function changeStatus(#[LiveArg] Attendance $attendance, #[LiveArg] string $newStatus, #[LiveArg] int $newIndex, MessageBusInterface $messageBus, EntityManagerInterface $em)
    {
        $this->denyAccessUnlessGranted('participants.view', $attendance->getOffer());

        if (null === $attendance->getParticipant()) {
            return;
        }

        $offer = $attendance->getOffer();
        $oldStatus = $attendance->getStatus();

        $attendance->setStatus($newStatus, $this->getUser());
        $attendance->setSorting(($newIndex * 128) + 64);

        if ($this->autoAssign) {
            $messageBus->dispatch(new AttendanceStatusChanged($attendance->getId(), $oldStatus, $attendance->getStatus()));
        }

        // Update participant list (move-up participants)
        // WHEN the current participant was not added to the wait-list explicitly,
        // otherwise, it might become confirmed immediately.
        if ($this->autoAssign && !$attendance->isWaitlisted()) {
            $messageBus->dispatch(new ParticipantListChanged($offer->getId()));
        }

        $em->flush();
    }

    #[LiveListener('indexUpdated')]
    public function changeIndex(#[LiveArg] Attendance $attendance, #[LiveArg] int $newIndex, EntityManagerInterface $em)
    {
        $this->denyAccessUnlessGranted('participants.view', $attendance->getOffer());

        $attendances = array_values($attendance->getOffer()->getAttendancesWithStatus($attendance->getStatus())->toArray());
        $fromIndex = array_search($attendance, $attendances, true);

        array_splice($attendances, $newIndex, 0, array_splice($attendances, $fromIndex, 1));

        $i = 0;
        foreach ($attendances as $a) {
            $a->setSorting(++$i * 128);
        }

        $em->flush();
    }
}
