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

use Doctrine\ORM\QueryBuilder;
use Ferienpass\CoreBundle\Entity\SentMessage;
use Ferienpass\CoreBundle\Pagination\Paginator;
use Ferienpass\CoreBundle\Repository\SentMessageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\ValidatableComponentTrait;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsLiveComponent(route: 'live_component_admin')]
class Outbox extends AbstractController
{
    use ComponentToolsTrait;
    use DefaultActionTrait;
    use ValidatableComponentTrait;

    #[LiveProp(writable: true)]
    public string $query = '';

    #[LiveProp(url: true)]
    public ?SentMessage $message = null;

    private QueryBuilder $qb;

    private int $page = 1;

    public function __construct(private readonly SentMessageRepository $repository)
    {
        $this->qb = $this->repository->createQueryBuilder('message');
    }

    #[ExposeInTemplate]
    public function getPagination(): Paginator
    {
        $this->addQueryBuilderSearch();

        return (new Paginator($this->qb, 50 * $this->page))->paginate();
    }

    #[LiveListener('open')]
    public function openMessage(#[LiveArg] SentMessage $message)
    {
        $this->message = $message;
    }

    #[LiveListener('loadMore')]
    public function loadMore()
    {
        ++$this->page;
    }

    private function addQueryBuilderSearch(): void
    {
        $where = $this->qb->expr()->andX();
        //        foreach (array_filter(StringUtil::trimsplit(' ', $this->query)) as $j => $token) {
        //            $or = $this->qb->expr()->orX();
        //
        //            foreach ($this->searchable as $i => $field) {
        //                $or->add("i.$field LIKE :query_$i$j");
        //                $this->qb->setParameter("query_$i$j", "%{$token}%");
        //            }
        //
        //            $where->add($or);
        //        }

        if ($where->count()) {
            $this->qb->andWhere($where);
        }
    }
}
