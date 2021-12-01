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

namespace Ferienpass\HostPortalBundle\ApplicationSystem;

use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;
use Ferienpass\CoreBundle\ApplicationSystem\ApplicationSystems;
use Ferienpass\CoreBundle\Entity\Attendance;
use Ferienpass\CoreBundle\Entity\Offer;
use Ferienpass\CoreBundle\Entity\Participant;
use Ferienpass\CoreBundle\Facade\AttendanceFacade;
use Ferienpass\CoreBundle\Message\AttendanceStatusChanged;
use Ferienpass\CoreBundle\Message\ParticipantListChanged;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class ParticipantList
{
    private MessageBusInterface $messageBus;
    private Connection $connection;
    private ApplicationSystems $applicationSystems;
    private AttendanceFacade $attendanceFacade;
    private ManagerRegistry $doctrine;

    public function __construct(
        MessageBusInterface $messageBus,
        Connection $connection,
        ApplicationSystems $applicationSystems,
        AttendanceFacade $attendanceFacade,
        ManagerRegistry $doctrine
    ) {
        $this->messageBus = $messageBus;
        $this->connection = $connection;
        $this->applicationSystems = $applicationSystems;
        $this->attendanceFacade = $attendanceFacade;
        $this->doctrine = $doctrine;
    }

    public function add(Offer $offer, array $data): void
    {
        if (!$data['firstname'] && !$data['lastname']) {
            throw new \InvalidArgumentException('Missing name');
        }

        $this->addParticipant($data, $offer);

        $this->dispatchMessage(new ParticipantListChanged($offer->getId()));
    }

    public function confirm(Offer $offer, Collection $participants): void
    {
        /** @var Collection|Attendance[] $attendances */
        $attendances = $offer->getAttendances()->filter(fn (Attendance $a) => $participants->contains($a->getParticipant()));

        foreach ($attendances as $attendance) {
            $oldStatus = $attendance->getStatus();
            if (Attendance::STATUS_CONFIRMED === $oldStatus) {
                continue;
            }

            $attendance->setStatus(Attendance::STATUS_CONFIRMED);

            $this->dispatchMessage(new AttendanceStatusChanged($attendance->getId(), $oldStatus, $attendance->getStatus()));
        }

        $this->doctrine->getManager()->flush();

        $this->dispatchMessage(new ParticipantListChanged($offer->getId()));
    }

    public function reject(Offer $offer, Collection $participants): void
    {
        /** @var Collection|Attendance[] $attendances */
        $attendances = $offer->getAttendances()->filter(fn (Attendance $a) => $participants->contains($a->getParticipant()));

        foreach ($attendances as $attendance) {
            $oldStatus = $attendance->getStatus();

            if (Attendance::STATUS_WITHDRAWN === $oldStatus) {
                continue;
            }

            $attendance->setStatus(Attendance::STATUS_WITHDRAWN);

            $this->dispatchMessage(new AttendanceStatusChanged($attendance->getId(), $oldStatus, $attendance->getStatus()));
        }

        $this->doctrine->getManager()->flush();

        $this->dispatchMessage(new ParticipantListChanged($offer->getId()));
    }

    private function addParticipant(array $data, Offer $offer): void
    {
        $applicationSystem = $this->applicationSystems->findApplicationSystem($offer);

        $expr = $this->connection->getExpressionBuilder();

        // Try to find an existing participant
        $statement = $this->connection->createQueryBuilder()
            ->select('p.id')
            ->from('Participant', 'p')
            ->leftJoin('p', 'tl_member', 'm', 'p.member=m.id')
            ->where(
                $expr->or(
                    $expr->and('p.phone<>\'\'', 'p.phone=:phone'),
                    $expr->and('m.phone<>\'\'', 'm.phone=:phone'),
                    $expr->and('p.email<>\'\'', 'p.email=:email'),
                    $expr->and('m.email<>\'\'', 'm.email=:email')
                )
            )
            ->andWhere($expr->and('p.firstname=:firstname', 'p.lastname=:lastname'))
            ->setParameter('phone', $data['phone'])
            ->setParameter('email', $data['email'])
            ->setParameter('firstname', $data['firstname'])
            ->setParameter('lastname', $data['lastname'])
            ->execute()
        ;

        if (false !== $participantId = $statement->fetchOne()) {
            $participant = $this->doctrine->getRepository(Participant::class)->find($participantId);
            $this->attendanceFacade->create($offer, $participant);

            return;
        }

        // Try to find an existing member for this participant
        $statement = $this->connection->createQueryBuilder()
            ->select('m.id')
            ->from('tl_member', 'm')
            ->where(
                $expr->or(
                    $expr->and('m.phone<>\'\'', 'm.phone=:phone'),
                    $expr->and('m.email<>\'\'', 'm.email=:email')
                )
            )
            ->setParameter('phone', $data['phone'])
            ->setParameter('email', $data['email'])
            ->execute()
        ;

        if (false !== $memberId = $statement->fetchOne()) {
            $participant = new Participant($memberId);
        } else {
            $participant = new Participant();
        }

        $participant->setEmail($data['email'] ?? null);
        $participant->setPhone($data['phone'] ?? null);
        $participant->setFirstname($data['firstname'] ?? null);
        $participant->setLastname($data['lastname'] ?? null);
        $participant->setMobile($data['mobile'] ?? null);

        $this->doctrine->getManager()->persist($participant);
        $this->doctrine->getManager()->flush();

        $this->attendanceFacade->create($offer, $participant);
    }

    private function dispatchMessage($message, array $stamps = []): Envelope
    {
        return $this->messageBus->dispatch($message, $stamps);
    }
}
