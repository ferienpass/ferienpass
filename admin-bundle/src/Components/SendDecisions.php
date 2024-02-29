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

use Ferienpass\CoreBundle\Applications\UnconfirmedApplications;
use Ferienpass\CoreBundle\Entity\Edition;
use Ferienpass\CoreBundle\Message\ConfirmApplications;
use Ferienpass\CoreBundle\Notification\MailingNotification;
use Ferienpass\CoreBundle\Notifier;
use Ferienpass\CoreBundle\Repository\EditionRepository;
use Ferienpass\CoreBundle\Repository\HostRepository;
use Ferienpass\CoreBundle\Repository\OfferRepository;
use Ferienpass\CoreBundle\Repository\ParticipantRepository;
use Ferienpass\CoreBundle\Repository\UserRepository;
use Ferienpass\CoreBundle\Session\Flash;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\ValidatableComponentTrait;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;
use Twig\Environment;

#[AsLiveComponent(route: 'live_component_admin')]
class SendDecisions extends AbstractController
{
    use ComponentToolsTrait;
    use DefaultActionTrait;
    use ValidatableComponentTrait;

    #[LiveProp(writable: true, url: true)]
    public ?Edition $edition = null;

    public function __construct(private readonly EditionRepository $editionRepository, private readonly ParticipantRepository $participantRepository, private readonly UserRepository $userRepository, private readonly HostRepository $hostRepository, private readonly OfferRepository $offerRepository, private readonly Environment $twig, private readonly RequestStack $requestStack, private readonly NormalizerInterface $normalizer, private readonly Notifier $notifier, private readonly MailingNotification $mailingNotification, private readonly UnconfirmedApplications $unconfirmedApplications)
    {
    }

    #[ExposeInTemplate]
    public function editionOptions()
    {
        $a = $this->unconfirmedApplications->getAttendanceIds();

        $qb = $this->editionRepository->createQueryBuilder('e')

        ;

        return $qb->getQuery()->getResult();
    }

    #[LiveAction]
    public function submit()
    {
        $this->validate();
        $this->dispatchBrowserEvent('admin:modal:open');
    }

    #[LiveAction]
    public function send(Flash $flash, MessageBusInterface $messageBus, UnconfirmedApplications $unconfirmedApplications)
    {
        $messageBus->dispatch(new ConfirmApplications($unconfirmedApplications->getAttendanceIds()));

        $flash->addConfirmation('Versand erfolgreich', 'Die E-Mails wurden versandt.');

        $this->edition = null;

        $this->dispatchBrowserEvent('admin:modal:close');
        $this->resetValidation();
    }

    #[LiveAction]
    public function cancel()
    {
        $this->dispatchBrowserEvent('admin:modal:close');
    }
}
