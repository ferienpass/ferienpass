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

#[AsLiveComponent(route: 'live_component_admin')]
class ListActivityPanel extends AbstractController
{
    use ComponentToolsTrait;
    use DefaultActionTrait;
    use ValidatableComponentTrait;

    #[LiveProp(hydrateWith: 'hydrateItem', dehydrateWith: 'dehydrateItem')]
    public ?object $item = null;

    #[LiveProp(writable: true)]
    #[NotBlank]
    public string $newComment = '';

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    #[LiveListener('view')]
    public function open(#[LiveArg] int $id, #[LiveArg] string $class)
    {
        $this->item = $this->entityManager->getRepository($class)->find($id);
        $this->newComment = '';

        $this->resetValidation();
        $this->dispatchBrowserEvent('admin:slideover:open');
    }

    public function activity()
    {
        if (null === $this->item) {
            return null;
        }

        $activity = [];
        $activity[] = $this->item->getActivity()->toArray();

        foreach ($this->item->getAttendances() as $attendance) {
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

        $comment = new ParticipantLog($this->item, $this->newComment, $user);

        $em->persist($comment);
        $em->flush();

        $this->newComment = '';
        $this->resetValidation();

        $this->emit('admin_list:changed');

        // $this->addFlash('success', 'Post saved!');
    }

    public function dehydrateItem(?object $item): array|null
    {
        if (null === $item) {
            return null;
        }

        return [$item::class, $item->getId()];
    }

    public function hydrateItem(?array $data): ?object
    {
        if (null === $data) {
            return null;
        }
        [$class, $id] = $data;

        return $this->entityManager->getRepository($class)->find($id);
    }
}
