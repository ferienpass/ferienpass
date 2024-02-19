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

use Ferienpass\CoreBundle\Entity\User;
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
use Symfony\Component\Notifier\Recipient\Recipient;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\ValidatableComponentTrait;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;
use Twig\Environment;
use Twig\Error\Error;

#[AsLiveComponent(route: 'live_component_admin')]
class Mailing extends AbstractController
{
    use ComponentToolsTrait;
    use DefaultActionTrait;
    use ValidatableComponentTrait;

    #[LiveProp(writable: true, url: true)]
    public ?string $group = null;

    #[LiveProp(writable: true)]
    public bool $hostsNotDisabled = true;

    #[LiveProp(writable: true)]
    public bool $hostsWithOffer = false;

    #[LiveProp(writable: true)]
    public array $editions = [];

    #[LiveProp(writable: true, onUpdated: 'onOffersUpdated', url: true)]
    public array $offers = [];

    #[LiveProp(writable: true, url: true)]
    public array $hosts = [];

    #[LiveProp(writable: true)]
    #[Assert\NotBlank()]
    public string $emailSubject = '';

    #[LiveProp(writable: true)]
    #[Assert\NotBlank()]
    public string $emailText = '';

    #[LiveProp(writable: true)]
    public array $attendanceStatus = [];

    public function __construct(private readonly EditionRepository $editionRepository, private readonly ParticipantRepository $participantRepository, private readonly UserRepository $userRepository, private readonly HostRepository $hostRepository, private readonly OfferRepository $offerRepository, private readonly Environment $twig, private readonly RequestStack $requestStack, private readonly NormalizerInterface $normalizer, private readonly Notifier $notifier, private readonly MailingNotification $mailingNotification)
    {
    }

    public function mount()
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->group = 'participants';
        }
    }


    #[LiveListener('group')]
    public function changeGroup(#[LiveArg] string $group)
    {
        $this->group = $group;
    }

    public function onOffersUpdated($previous): void
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return;
        }

        $this->offers = $previous;
    }

    #[ExposeInTemplate]
    public function countAllHosts()
    {
        $qb = $this->userRepository->createQueryBuilder('u')
            ->innerJoin('u.hostAssociations', 'ha')
            ->innerJoin('ha.host', 'host')
        ;

        $qb->select('COUNT(DISTINCT u.email)');

        return $qb->getQuery()->getSingleScalarResult();
    }

    #[ExposeInTemplate]
    public function countAllParticipants()
    {
        $qb = $this->participantRepository->createQueryBuilder('p')
            ->innerJoin('p.attendances', 'attendances')
            ->innerJoin('attendances.offer', 'offer')
            ->leftJoin('p.user', 'u')
        ;

        $qb->select('COUNT(DISTINCT COALESCE(p.email, u.email))');

        return $qb->getQuery()->getSingleScalarResult();
    }

    #[ExposeInTemplate]
    public function context()
    {
        $context = [];

        $context['baseUrl'] = $this->requestStack->getCurrentRequest()?->getSchemeAndHttpHost().$this->requestStack->getCurrentRequest()?->getBaseUrl();

        if (1 === \count($this->editions)) {
            $context['edition'] = $this->editionRepository->find(array_values($this->editions)[0]);
        }
        if (1 === \count($this->offers)) {
            $context['offer'] = $this->offerRepository->find(array_values($this->offers)[0]);
        }
        if (1 === \count($this->hosts)) {
            $context['host'] = $this->hostRepository->find(array_values($this->hosts)[0]);
        }

        return $context;
    }

    #[ExposeInTemplate]
    public function editionOptions()
    {
        return $this->editionRepository->findBy(['archived' => 0]);
    }

    #[ExposeInTemplate]
    public function offerOptions()
    {
        $qb = $this->offerRepository->createQueryBuilder('o')
            ->where('o.id IN (:ids)')
            ->setParameter('ids', $this->offers);

        if (!$this->isGranted('ROLE_ADMIN')) {
            $user = $this->getUser();
            $qb->innerJoin('o.hosts', 'hosts')->andWhere('hosts IN (:hosts)')->setParameter('hosts', $user instanceof User ? $user->getHosts() : []);
        }

        return $qb->getQuery()->getResult();
    }

    #[ExposeInTemplate]
    public function hostOptions()
    {
        $qb = $this->hostRepository->createQueryBuilder('h')
            ->where('h.id IN (:ids)')
            ->setParameter('ids', $this->hosts)
        ;

        if (!$this->isGranted('ROLE_ADMIN')) {
            $user = $this->getUser();
            $qb->andWhere('h.id IN (:hosts)')->setParameter('hosts', $user instanceof User ? $user->getHosts() : []);
        }

        return $qb->getQuery()->getResult();
    }

    #[ExposeInTemplate]
    public function recipients()
    {
        $return = [];

        if ('hosts' === $this->group) {
            foreach ($this->queryHostAccounts() as $item) {
                $return[$item->getEmail()][] = $item;
            }
        } elseif ('participants' === $this->group) {
            foreach ($this->queryParticipants() as $item) {
                $return[$item->getEmail()][] = $item;
            }
        }

        return $return;
    }

    #[ExposeInTemplate]
    public function preview(): string|false
    {
        try {
            return $this->twig->createTemplate($this->emailText)->render($this->context());
        } catch (Error) {
            return false;
        }
    }

    #[ExposeInTemplate]
    public function availableTokens()
    {
        $availableTokens = [];

        foreach ($this->context() as $token => $object) {
            switch ($token) {
                case 'baseUrl':
                    $availableTokens[$token] = $object;
                    break;
                case 'edition':
                case 'offer':
                case 'host':
                    $tokens = $this->normalizer->normalize($object, context: ['groups' => 'notification']);
                    foreach (array_keys($tokens) as $property) {
                        $availableTokens["$token.$property"] = $this->container->get('twig')->createTemplate(sprintf('{{ %s }}', "$token.$property"))->render([$token => $tokens]);
                    }
                    break;
            }
        }

        return $availableTokens;
    }

    #[LiveAction]
    public function submit()
    {
        $this->validate();
        $this->dispatchBrowserEvent('admin:modal:open');
    }

    #[LiveAction]
    public function send(Flash $flash)
    {
        foreach (array_keys($this->recipients()) as $email) {
            $notification = clone $this->mailingNotification;
            $notification->subject($this->emailSubject);
            $notification->content($this->emailText);
            $notification->context($this->context());

            $this->notifier->send($notification, new Recipient($email));
        }

        $flash->addConfirmation('Versand erfolgreich', 'Die E-Mails wurden versandt.');

        $this->emailSubject = '';
        $this->emailText = '';

        $this->dispatchBrowserEvent('admin:modal:close');
        $this->resetValidation();
    }

    #[LiveAction]
    public function cancel()
    {
        $this->dispatchBrowserEvent('admin:modal:close');
    }

    private function queryHostAccounts()
    {
        $qb = $this->userRepository
            ->createQueryBuilder('u')
            ->innerJoin('u.hostAssociations', 'ha')
            ->innerJoin('ha.host', 'host')
        ;

        if ($this->hostsNotDisabled) {
            $qb->andWhere('u.disable = :disabled')->setParameter('disabled', 0);
        }

        if ($this->hostsWithOffer) {
            $qb->innerJoin('host.offers', 'offers');
            $qb->leftJoin('offers.edition', 'edition');

            if ($this->editions) {
                $qb->andWhere('edition IN (:editions)')->setParameter('editions', $this->editions);
            }
        }

        if ($this->hosts) {
            $qb->andWhere('host IN (:hosts)')->setParameter('hosts', $this->hosts);
        }

        return $qb->getQuery()->getResult();
    }

    private function queryParticipants()
    {
        $qb = $this->participantRepository
            ->createQueryBuilder('p')
            ->innerJoin('p.attendances', 'attendances')
            ->innerJoin('attendances.offer', 'offer')
            ->leftJoin('offer.edition', 'edition')
        ;

        if (!$this->isGranted('ROLE_ADMIN')) {
            $user = $this->getUser();
            $qb->innerJoin('offer.hosts', 'hosts')->andWhere('hosts IN (:hosts)')->setParameter('hosts', $user instanceof User ? $user->getHosts() : []);
        }

        if ($this->offers) {
            $qb->andWhere('offer IN (:offers)')->setParameter('offers', $this->offers);
        }

        if ($this->editions) {
            $qb->andWhere('edition IN (:editions)')->setParameter('editions', $this->editions);
        }

        if ($this->attendanceStatus) {
            $qb->andWhere('attendances.status IN (:status)')->setParameter('status', $this->attendanceStatus);
        }

        return $qb->getQuery()->getResult();
    }
}
