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

use Contao\StringUtil;
use Doctrine\ORM\QueryBuilder;
use Ferienpass\AdminBundle\Form\Filter\AbstractFilter;
use Ferienpass\AdminBundle\Form\Filter\FilterRegistry;
use Ferienpass\CoreBundle\Pagination\Paginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(name: 'SearchableQueryableList', template: '@FerienpassAdmin/components/SearchableQueryableList.html.twig')]
class SearchableQueryableList extends AbstractController
{
    use ComponentWithFormTrait;
    use DefaultActionTrait;

    #[LiveProp]
    public array $config;

    #[LiveProp]
    public array $searchable;

    #[LiveProp(writable: true)]
    public string $query = '';

    #[LiveProp]
    public array $initialFormData = [];

    #[LiveProp]
    public QueryBuilder $qb;

    #[LiveProp]
    public ?string $originalRoute = null;
    #[LiveProp]
    public ?array $originalQuery = null;

    #[LiveProp(writable: true)]
    public string $sorting = '';

    public function __construct(private readonly FormFactoryInterface $formFactory, private readonly FilterRegistry $filterRegistry)
    {
    }

    public function getPagination(): Paginator
    {
        $this->addQueryBuilderSearch();
        $this->addQueryBuilderSorting();

        if ('' !== $this->query) {
            unset($this->originalQuery['page']);
        }

        return (new Paginator($this->qb, 50))->paginate((int) ($this->originalQuery['page'] ?? 1));
    }

    public function getSortingFields(): array
    {
        return $this->getFilter()?->getSearchable() ?? [];
    }

    #[LiveAction]
    public function filter()
    {
        if (null === $filter = $this->getFilter()) {
            return $this->redirectToRoute($this->originalRoute);
        }

        $this->submitForm();

        $filter->apply($this->qb, $this->getForm());

        return $this->redirectToRoute($this->originalRoute);
    }

    protected function instantiateForm(): FormInterface
    {
        if (null === $filter = $this->getFilter()) {
            return $this->formFactory->create();
        }

        return $this->formFactory->create($filter::class, $this->initialFormData);
    }

    private function addQueryBuilderSearch(): void
    {
        $where = $this->qb->expr()->andX();
        foreach (array_filter(StringUtil::trimsplit(' ', $this->query)) as $j => $token) {
            $or = $this->qb->expr()->orX();

            foreach ($this->searchable as $i => $field) {
                $or->add("i.$field LIKE :query_$i$j");
                $this->qb->setParameter("query_$i$j", "%{$token}%");
            }

            $where->add($or);
        }

        if ($where->count()) {
            $this->qb->andWhere($where);
        }
    }

    private function addQueryBuilderSorting(): void
    {
        $sorting = $this->getFilter()?->getOrderByFor($this->sorting);
        if ($sorting) {
            $this->qb->addOrderBy(...(array) $sorting);
        }
    }

    private function getFilter(): ?AbstractFilter
    {
        $entity = explode(' ', (string) $this->qb->getDQLPart('from')[0], 2)[0];

        return $this->filterRegistry->byEntity($entity);
    }
}
