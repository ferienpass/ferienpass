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

namespace Ferienpass\AdminBundle\ApplicationSystem;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;
use Ferienpass\CoreBundle\ApplicationSystem\ApplicationSystems;
use Ferienpass\CoreBundle\Entity\Attendance;
use Ferienpass\CoreBundle\Entity\Offer;
use Ferienpass\CoreBundle\Entity\Participant;
use Ferienpass\CoreBundle\Facade\AttendanceFacade;
use Ferienpass\CoreBundle\Message\AttendanceStatusChanged;
use Ferienpass\CoreBundle\Message\ParticipantListChanged;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

class ParticipantList
{
    public function __construct(private readonly MessageBusInterface $messageBus, private readonly Connection $connection, private readonly ApplicationSystems $applicationSystems, private readonly AttendanceFacade $attendanceFacade, private readonly ManagerRegistry $doctrine, private readonly Security $security)
    {
    }

    public function add(Offer $offer, array $data): void
    {
        if (!$data['firstname'] && !$data['lastname']) {
            throw new \InvalidArgumentException('Missing name');
        }

        $this->addParticipant($data, $offer);

        $this->dispatchMessage(new ParticipantListChanged($offer->getId()));
    }

    /**
     * @param Attendance[] $attendances
     */
    public function confirm(array $attendances, bool $reorder = false, bool $notify = true): void
    {
        foreach ($attendances as $attendance) {
            if (null === $attendance->getParticipant()) {
                continue;
            }

            $oldStatus = $attendance->getStatus();
            if (Attendance::STATUS_CONFIRMED === $oldStatus) {
                continue;
            }

            $attendance->setStatus(Attendance::STATUS_CONFIRMED, $this->security->getUser());

            $this->dispatchMessage(new AttendanceStatusChanged($attendance->getId(), $oldStatus, $attendance->getStatus(), $notify));
        }

        $this->doctrine->getManager()->flush();

        if (false === $reorder) {
            return;
        }

        foreach (array_unique(array_map(fn (Attendance $a) => $a->getOffer()->getId(), $attendances)) as $offerId) {
            $this->dispatchMessage(new ParticipantListChanged($offerId));
        }
    }

    /**
     * @param Attendance[] $attendances
     */
    public function reject(array $attendances, bool $reorder = false, bool $notify = true): void
    {
        foreach ($attendances as $attendance) {
            if (null === $attendance->getParticipant()) {
                continue;
            }

            $oldStatus = $attendance->getStatus();

            if (Attendance::STATUS_WITHDRAWN === $oldStatus) {
                continue;
            }

            $attendance->setStatus(Attendance::STATUS_WITHDRAWN, $this->security->getUser());

            $this->dispatchMessage(new AttendanceStatusChanged($attendance->getId(), $oldStatus, $attendance->getStatus(), $notify));
        }

        $this->doctrine->getManager()->flush();

        if (false === $reorder) {
            return;
        }

        foreach (array_unique(array_map(fn (Attendance $a) => $a->getOffer()->getId(), $attendances)) as $offerId) {
            $this->dispatchMessage(new ParticipantListChanged($offerId));
        }
    }

    private function addParticipant(array $data, Offer $offer): void
    {
        $applicationSystem = $this->applicationSystems->findApplicationSystem($offer);

        $expr = $this->connection->createExpressionBuilder();

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
            ->executeQuery()
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
            ->executeQuery()
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
