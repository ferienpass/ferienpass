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

use Doctrine\DBAL\Types\Types;
use Ferienpass\CoreBundle\Export\Offer\OfferExporter;
use Ferienpass\CoreBundle\Repository\EditionRepository;
use Ferienpass\CoreBundle\Repository\OfferRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
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

#[AsLiveComponent(route: 'live_component_admin')]
final class Export extends AbstractController
{
    use ComponentToolsTrait;
    use DefaultActionTrait;
    use ValidatableComponentTrait;

    #[LiveProp(writable: true)]
    #[Assert\NotBlank()]
    public ?string $export = null;

    #[LiveProp(writable: true)]
    public array $editions = [];

    #[LiveProp(writable: true)]
    public array $hosts = [];

    #[LiveProp(writable: true)]
    public bool $onlyPublished = true;

    public function __construct(private readonly OfferRepository $offerRepository, private readonly EditionRepository $editionRepository, private readonly OfferExporter $exporter)
    {
    }

    #[ExposeInTemplate]
    public function exporterOptions()
    {
        return $this->exporter->getAllNames();
    }

    #[ExposeInTemplate]
    public function editionOptions()
    {
        return $this->editionRepository->findBy(['archived' => 0]);
    }

    #[LiveListener('selectExport')]
    public function selectExport(#[LiveArg] string $name)
    {
        $this->export = $name;
    }

    #[LiveAction]
    public function submit(): BinaryFileResponse
    {
        $this->validate();

        $offers = $this->queryOffers();

        return $this->file($this->exporter->getExporter($this->export)->generate($offers));
    }

    private function queryOffers(): iterable
    {
        $qb = $this->offerRepository
            ->createQueryBuilder('offer')
            ->leftJoin('offer.dates', 'dates')
            ->orderBy('dates.begin', 'ASC')
        ;

        if ($this->onlyPublished) {
            $qb->andWhere('offer.published = 1');
        }

        if ($this->editions) {
            $qb->andWhere('offer.edition IN (:editions)')->setParameter('editions', $this->editions, Types::SIMPLE_ARRAY);
        }

        if ($this->hosts) {
            $qb->innerJoin('offer.hosts', 'hosts')->andWhere('hosts.id IN (:hosts)')->setParameter('hosts', $this->hosts, Types::SIMPLE_ARRAY);
        }

        return $qb->getQuery()->getResult();
    }
}
