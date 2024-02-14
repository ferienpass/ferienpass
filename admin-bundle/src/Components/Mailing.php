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

use Ferienpass\CoreBundle\Repository\EditionRepository;
use Ferienpass\CoreBundle\Repository\HostRepository;
use Ferienpass\CoreBundle\Repository\OfferRepository;
use Ferienpass\CoreBundle\Repository\ParticipantRepository;
use Ferienpass\CoreBundle\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\ValidatableComponentTrait;
use Twig\Environment;

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

    #[LiveProp(writable: true, url: true)]
    public array $offers = [];

    #[LiveProp(writable: true, url: true)]
    public array $hosts = [];

    #[LiveProp(writable: true)]
    public string $emailText = '';

    #[LiveProp(writable: true)]
    public array $attendanceStatus = [];

    public function __construct(private readonly EditionRepository $editionRepository, private readonly ParticipantRepository $participantRepository, private readonly UserRepository $userRepository, private readonly HostRepository $hostRepository, private readonly OfferRepository $offerRepository, private readonly Environment $twig)
    {
    }

    #[LiveListener('group')]
    public function changeGroup(#[LiveArg] string $group)
    {
        $this->group = $group;
    }

    public function countAllHosts()
    {
        $qb = $this->userRepository->createQueryBuilder('u')
            ->innerJoin('u.hostAssociations', 'ha')
            ->innerJoin('ha.host', 'host')
        ;

        $qb->select('COUNT(DISTINCT u.email)');

        return $qb->getQuery()->getSingleScalarResult();
    }

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

    public function context()
    {
        $context = [];

        if (1 === \count($this->editions)) {
            $context['edition'] = array_values($this->editions)[0];
        }
        if (1 === \count($this->offers)) {
            $context['offer'] = array_values($this->offers)[0];
        }
        if (1 === \count($this->hosts)) {
            $context['host'] = array_values($this->hosts)[0];
        }

        return $context;
    }

    public function editionOptions()
    {
        return $this->editionRepository->findBy(['archived' => 0]);
    }

    public function offerOptions()
    {
        return $this->offerRepository->findBy(['id' => $this->offers]);
    }

    public function hostOptions()
    {
        return $this->hostRepository->findBy(['id' => $this->hosts]);
    }

    public function recipients()
    {
        $return = [];

        if ('hosts' === $this->group) {
            foreach ($this->queryHostAccounts() as $item) {
                $return[$item->getEmail()][] = $item;
            }
        } else {
            foreach ($this->queryParticipants() as $item) {
                $return[$item->getEmail()][] = $item;
            }
        }

        return $return;
    }

    public function preview()
    {
        return $this->twig->createTemplate($this->emailText)->render($this->context());
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
