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

namespace Ferienpass\CoreBundle\Applications;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;

class UnconfirmedApplications
{
    private Connection $connection;

    private ?array $uninformedMembers = null;
    private ?array $uninformedParticipants = null;
    private ?array $attendanceIds = null;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function getUninformedMembers(): array
    {
        if (null === $this->uninformedMembers) {
            $this->fetchAttendances();
        }

        return (array) $this->uninformedMembers;
    }

    public function getUninformedParticipants(): array
    {
        if (null === $this->uninformedParticipants) {
            $this->fetchAttendances();
        }

        return (array) $this->uninformedParticipants;
    }

    public function getAttendanceIds(): array
    {
        if (null === $this->attendanceIds) {
            $this->fetchAttendances();
        }

        return (array) $this->attendanceIds;
    }

    /**
     * Fetch all attendances that were not sent already grouped by member.
     */
    private function fetchAttendances(): void
    {
        // Fetch attendances for that a notification was already sent.
        $qb2 = $this->connection->createQueryBuilder()
            ->select('elr.relatedId')
            ->from('NotificationLog', 'nl')
            ->innerJoin('nl', 'EventLog', 'el', 'el.id=nl.log_id')
            ->innerJoin('el', 'EventLogRelated', 'elr', 'el.id = elr.log_id')
            ->innerJoin('elr', 'Attendance', 'a', 'elr.relatedId = a.id')
            ->where("elr.relatedTable = 'Attendance'")
            ->andWhere('el.createdAt >= a.modifiedAt')
        ;

        $statement = $this->connection->createQueryBuilder()
            ->select(
                'DISTINCT a.id as attendance_id',
                'IF(o.cancelled, "error", a.status) as attendance_status',
                //'a.status as attendance_status',
                'p.id as participant_id',
                'p.firstname as participant_firstname',
                'p.lastname as participant_lastname',
                'p.email as participant_email',
                'o.id as offer_id',
                'o.name as offer_name',
                'd.begin as offer_date_start',
                'o.requiresAgreementLetter as offer_agreement_letter',
                'o.cancelled as offer_cancelled',
                'm.id as member_id',
                'm.email as member_email',
                'm.firstname as member_firstname',
                'm.lastname as member_lastname',
                'm.email as member_email',
            )
            ->from('Participant', 'p')

            // The participant may belong to a parent
            ->leftJoin('p', 'tl_member', 'm', 'p.member_id=m.id')

            // We want participants with attendances
            ->innerJoin('p', 'Attendance', 'a', 'a.participant_id=p.id')

            // Additionally fetch the offer
            ->innerJoin('a', 'Offer', 'o', 'a.offer_id=o.id')

            // Additionally fetch the offer dates
            ->innerJoin('o', 'OfferDate', 'd', 'd.offer_id=o.id')

            // Attendances NOT have been informed yet
            ->where($qb2->expr()->notIn('a.id', $qb2->getSQL()))

            // Inform attendances that have any status but withdrawn and waiting
            ->andWhere('a.status NOT IN (:status)')
            ->setParameter('status', ['withdrawn', 'waiting'], Connection::PARAM_STR_ARRAY);

        $this->attendanceIds = array_map('intval', $statement->execute()->fetchAll(FetchMode::COLUMN));

        $result = $statement->execute()->fetchAll(FetchMode::ASSOCIATIVE);

        $members = [];
        $participants = [];

        foreach ($result as $dataset) {
            if (null === $dataset['member_id']) {
                $participants[$dataset['participant_id']][$dataset['attendance_id']] = $dataset;
                continue;
            }

            $members[$dataset['member_id']][$dataset['participant_id']][$dataset['attendance_id']] = $dataset;
        }

        $this->uninformedMembers = $members;
        $this->uninformedParticipants = $participants;
    }
}
