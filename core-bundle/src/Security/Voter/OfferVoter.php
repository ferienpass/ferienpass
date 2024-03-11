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

namespace Ferienpass\CoreBundle\Security\Voter;

use Ferienpass\CoreBundle\Entity\Host;
use Ferienpass\CoreBundle\Entity\Offer\OfferEntityInterface;
use Ferienpass\CoreBundle\Entity\OfferDate;
use Ferienpass\CoreBundle\Entity\User;
use Ferienpass\CoreBundle\Repository\AttendanceRepository;
use Ferienpass\CoreBundle\Repository\HostRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class OfferVoter extends Voter
{
    public function __construct(private readonly Security $security, private readonly AttendanceRepository $attendanceRepository, private readonly HostRepository $hostRepository)
    {
    }

    protected function supports($attribute, $subject): bool
    {
        $operations = [
            'view',
            'create',
            'edit',
            'copy',
            'delete',
            'freeze',
            OfferEntityInterface::TRANSITION_COMPLETE,
            OfferEntityInterface::TRANSITION_APPROVE,
            OfferEntityInterface::TRANSITION_PUBLISH,
            OfferEntityInterface::TRANSITION_UNPUBLISH,
            OfferEntityInterface::TRANSITION_RELAUNCH,
            OfferEntityInterface::TRANSITION_CANCEL,
            'participants.view',
            'participants.add',
            'participants.reject',
            'participants.confirm',
        ];

        return $subject instanceof OfferEntityInterface && \in_array($attribute, $operations, true);
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        /** @var OfferEntityInterface $offer */
        $offer = $subject;

        return match ($attribute) {
            'view' => $this->canView($offer, $user),
            'edit' => $this->canEdit($offer, $user),
            'create' => $this->canCreate($offer),
            'copy' => $this->canCopy($offer, $user),
            'delete' => $this->canDelete($offer, $user),
            'freeze' => $this->canFreeze($offer, $user),
            OfferEntityInterface::TRANSITION_CANCEL => $this->canCancel($offer, $user),
            OfferEntityInterface::TRANSITION_RELAUNCH => $this->canRelaunch($offer, $user),
            OfferEntityInterface::TRANSITION_PUBLISH => $this->canPublish($offer, $user),
            OfferEntityInterface::TRANSITION_UNPUBLISH => $this->canUnPublish($offer, $user),
            OfferEntityInterface::TRANSITION_APPROVE => $this->canApprove($offer, $user),
            OfferEntityInterface::TRANSITION_COMPLETE => $this->canComplete($offer, $user),
            'participants.view' => $this->canViewParticipants($offer, $user),
            'participants.add' => $this->canAddParticipants($offer, $user),
            'participants.reject' => $this->canRejectParticipants($offer, $user),
            'participants.confirm' => $this->canConfirmParticipants($offer, $user),
            default => throw new \LogicException('This code should not be reached!'),
        };
    }

    private function canView(OfferEntityInterface $offer, User $user): bool
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        $userHosts = $this->hostRepository->findByUser($user);
        $userHostIds = array_map(fn (Host $host) => $host->getId(), $userHosts);

        return $offer->getHosts()->filter(fn (Host $host) => \in_array($host->getId(), $userHostIds, true))->count() > 0;
    }

    private function canEdit(OfferEntityInterface $offer, User $user): bool
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        if (false === $this->canView($offer, $user)) {
            return false;
        }

        return (null === ($edition = $offer->getEdition())) || $edition->isEditableForHosts();
    }

    private function canCreate(OfferEntityInterface $offer): bool
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        if (null === $edition = $offer->getEdition()) {
            return true;
        }

        return !$edition->getActiveTasks('host_editing_stage')->isEmpty();
    }

    private function canCopy(OfferEntityInterface $offer, User $user): bool
    {
        return $this->canCreate($offer) && $this->canView($offer, $user);
    }

    private function canDelete(OfferEntityInterface $offer, User $user): bool
    {
        if (false === $this->canView($offer, $user)) {
            return false;
        }

        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        if (!$offer->getAttendances()->isEmpty()) {
            return false;
        }

        return null === ($edition = $offer->getEdition()) || $edition->isEditableForHosts();
    }

    private function canCancel(OfferEntityInterface $offer, User $user): bool
    {
        if (false === $this->canView($offer, $user)) {
            return false;
        }

        if ($this->offerIsImmutable($offer)) {
            return false;
        }

        return true;
    }

    private function canFreeze(OfferEntityInterface $offer, User $user): bool
    {
        if (false === $this->canView($offer, $user)) {
            return false;
        }

        if ($this->offerIsImmutable($offer)) {
            return false;
        }

        $attendances = $this->attendanceRepository->findBy(['offer' => $offer]);

        return 0 === \count($attendances);
    }

    private function canRelaunch(OfferEntityInterface $offer, User $user): bool
    {
        if (false === $this->canView($offer, $user)) {
            return false;
        }

        if ($this->offerIsImmutable($offer)) {
            return false;
        }

        return true;
    }

    private function canApprove(OfferEntityInterface $offer, User $user): bool
    {
        return $this->security->isGranted('ROLE_ADMIN');
    }

    private function canPublish(OfferEntityInterface $offer, User $user): bool
    {
        return $this->security->isGranted('ROLE_ADMIN');
    }

    private function canUnPublish(OfferEntityInterface $offer, User $user): bool
    {
        return $this->security->isGranted('ROLE_ADMIN');
    }

    private function canComplete(OfferEntityInterface $offer, User $user): bool
    {
        return true;
    }

    private function canViewParticipants(OfferEntityInterface $offer, User $user): bool
    {
        return $this->canView($offer, $user);
    }

    private function canRejectParticipants(OfferEntityInterface $offer, User $user): bool
    {
        if ($this->offerIsImmutable($offer)) {
            return false;
        }

        return $this->canView($offer, $user);
    }

    private function canConfirmParticipants(OfferEntityInterface $offer, User $user): bool
    {
        if ($this->offerIsImmutable($offer)) {
            return false;
        }

        return $this->canView($offer, $user);
    }

    private function canAddParticipants(OfferEntityInterface $offer, User $user): bool
    {
        if ($this->offerIsImmutable($offer)) {
            return false;
        }

        return $this->canView($offer, $user);
    }

    private function offerIsImmutable(OfferEntityInterface $offer): bool
    {
        if ($offer->getDates()->isEmpty()) {
            return false;
        }

        /** @var OfferDate $date */
        $date = $offer->getDates()->first();

        return (new \DateTimeImmutable()) >= $date->getBegin();
    }
}
