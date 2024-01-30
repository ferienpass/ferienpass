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

use Ferienpass\CoreBundle\Entity\Offer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
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
}
