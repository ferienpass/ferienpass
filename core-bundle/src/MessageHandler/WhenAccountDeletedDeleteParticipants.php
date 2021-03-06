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

namespace Ferienpass\CoreBundle\MessageHandler;

use Ferienpass\CoreBundle\Message\AccountDeleted;
use Ferienpass\CoreBundle\Message\ParticipantListChanged;
use Ferienpass\CoreBundle\Repository\AttendanceRepository;
use Ferienpass\CoreBundle\Repository\OfferRepository;
use Ferienpass\CoreBundle\Repository\ParticipantRepository;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class WhenAccountDeletedDeleteParticipants implements MessageHandlerInterface
{
    private MessageBusInterface $messageBus;
    private ParticipantRepository $participantRepository;
    private AttendanceRepository $attendanceRepository;
    private OfferRepository $offerRepository;

    public function __construct(MessageBusInterface $messageBus, ParticipantRepository $participantRepository, AttendanceRepository $attendanceRepository, OfferRepository $offerRepository)
    {
        $this->messageBus = $messageBus;
        $this->participantRepository = $participantRepository;
        $this->attendanceRepository = $attendanceRepository;
        $this->offerRepository = $offerRepository;
    }

    public function __invoke(AccountDeleted $message)
    {
        $offers = $this->offerRepository->createQueryBuilder('o')
            ->select('o.id')
            ->innerJoin('o.attendances', 'a')
            ->innerJoin('a.participant', 'p')
            ->where('p.member = :member')
            ->setParameter('member', $message->getUserId())
            ->getQuery()
            ->getResult()
        ;

        // Delete attendances
        $attendances = $this->attendanceRepository->createQueryBuilder('a')
            ->select('a.id')
            ->join('a.participant', 'p')
            ->where('p.member = :member')
            ->setParameter('member', $message->getUserId())
            ->getQuery()
            ->getResult()
        ;

        if (!empty($attendances)) {
            $this->attendanceRepository->createQueryBuilder('a')
                ->where('a.id in (:ids)')
                ->setParameter('ids', array_column($attendances, 'id'))
                ->delete()
                ->getQuery()
                ->execute()
            ;
        }

        // Delete participants
        $this->participantRepository->createQueryBuilder('p')
            ->where('p.member = :member')
            ->setParameter('member', $message->getUserId())
            ->delete()
            ->getQuery()
            ->execute()
        ;

        foreach ($offers as $offer) {
            $this->messageBus->dispatch(new ParticipantListChanged((int) $offer['id']));
        }
    }
}
