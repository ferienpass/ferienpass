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

use Ferienpass\AdminBundle\Form\EditAccessCodesType;
use Ferienpass\CoreBundle\Entity\AccessCodeStrategy;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\LiveCollectionTrait;

#[AsLiveComponent(template: '@FerienpassAdmin/components/EditForm.html.twig', route: 'live_component_admin')]
class EditAccessCodeStrategyForm extends AbstractController
{
    use DefaultActionTrait;
    use LiveCollectionTrait;

    #[LiveProp]
    public AccessCodeStrategy $initialFormData;

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(EditAccessCodesType::class, $this->initialFormData);
    }
}
