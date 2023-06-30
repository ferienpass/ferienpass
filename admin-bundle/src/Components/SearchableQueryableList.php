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
use Ferienpass\CoreBundle\Pagination\Paginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(template: '@FerienpassAdmin/components/SearchableQueryableList.html.twig')]
class SearchableQueryableList extends AbstractController
{
    use DefaultActionTrait;

    #[LiveProp]
    public array $config;

    #[LiveProp]
    public QueryBuilder $qb;

    #[LiveProp(writable: true)]
    public string $query = '';

    public function getPagination(Request $request): Paginator
    {
        $qb = $this->qb->andWhere('i.lastname LIKE :query')->setParameter('query', '%'.$this->query.'%');

        $items = $qb->getQuery()->getResult();

        return (new Paginator($qb, 100))->paginate($request->query->getInt('page', 1));
    }
}
