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
use Ferienpass\CoreBundle\Entity\AttendanceLog;
use Ferienpass\CoreBundle\Entity\Participant;
use Ferienpass\CoreBundle\Entity\ParticipantLog;
use Ferienpass\CoreBundle\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\ValidatableComponentTrait;

#[AsLiveComponent(name: 'SlideOver', route: 'live_component_admin', template: '@FerienpassAdmin/components/SlideOver.html.twig')]
class SlideOver extends AbstractController
{
    use ComponentToolsTrait;
    use DefaultActionTrait;
    use ValidatableComponentTrait;

    #[LiveProp]
    public ?Participant $participant = null;

    #[LiveProp(writable: true)]
    #[NotBlank]
    public string $newComment = '';

    #[LiveListener('view')]
    public function view(#[LiveArg] Participant $participant)
    {
        $this->newComment = '';
        $this->participant = $participant;
        $this->resetValidation();
        $this->dispatchBrowserEvent('foo');
    }

    public function activity()
    {
        if (null === $this->participant) {
            return null;
        }

        $activity = [];
        $activity[] = $this->participant->getActivity()->toArray();

        foreach ($this->participant->getAttendances() as $attendance) {
            $activity[] = $attendance->getActivity()->toArray();
        }

        $activity = array_merge(...$activity);
        usort($activity, fn (AttendanceLog|ParticipantLog $a, AttendanceLog|ParticipantLog $b) => $a->getCreatedAt() <=> $b->getCreatedAt());

        return $activity;
    }

    #[LiveAction]
    public function comment(EntityManagerInterface $em)
    {
        $this->validate();

        $user = $this->getUser();
        if (!$user instanceof User) {
            return;
        }

        $comment = new ParticipantLog($this->participant, $this->newComment, $user);

        $em->persist($comment);
        $em->flush();

        $this->newComment = '';
        $this->resetValidation();

        $this->addFlash('success', 'Post saved!');
    }
}
