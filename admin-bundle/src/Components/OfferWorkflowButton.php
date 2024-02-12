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
use Ferienpass\CoreBundle\Entity\Offer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(route: 'live_component_admin')]
class OfferWorkflowButton extends AbstractController
{
    use DefaultActionTrait;

    #[LiveProp]
    public Offer $offer;

    public function __construct(private readonly WorkflowInterface $offerStateMachine)
    {
    }

    public function transitions()
    {
        return $this->offerStateMachine->getEnabledTransitions($this->offer);
    }

    #[LiveAction]
    public function apply(#[LiveArg] string $transition, EntityManagerInterface $entityManager)
    {
        $this->offerStateMachine->apply($this->offer, $transition);
        $entityManager->flush();
    }
}
