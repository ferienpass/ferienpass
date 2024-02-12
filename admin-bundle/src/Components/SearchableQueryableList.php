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
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent(route: 'live_component_admin')]
class SearchableQueryableList extends AbstractController
{
    use ComponentToolsTrait;
    use ComponentWithFormTrait;
    use DefaultActionTrait;

    #[LiveProp]
    public array $config;

    #[LiveProp]
    public array $searchable;

    #[LiveProp(writable: true)]
    public string $query = '';

    #[LiveProp]
    public QueryBuilder $qb;

    #[LiveProp]
    public ?string $routeName = null;
    #[LiveProp]
    public ?array $routeParameters = null;

    #[LiveProp(writable: true)]
    public string $sorting = '';

    public function __construct(private readonly FormFactoryInterface $formFactory, private readonly FilterRegistry $filterRegistry)
    {
    }

    public function getPagination(): Paginator
    {
        $this->addQueryBuilderSearch();
        $this->addQueryBuilderSorting();

        if (null !== $filter = $this->getFilter()) {
            $filter->apply($this->qb, $this->getForm());
        }

        if ('' !== $this->query) {
            unset($this->routeParameters['page']);
        }

        return (new Paginator($this->qb, 50))->paginate((int) ($this->routeParameters['page'] ?? 1));
    }

    #[LiveListener('admin_list:changed')]
    public function changed()
    {
        // no need to do anything here: the component will re-render
    }

    public function getSortingFields(): array
    {
        return $this->getFilter()?->getSearchable() ?? [];
    }

    public function getFilters(): array
    {
        return $this->getFilter()?->getFilterable() ?? [];
    }

    #[LiveAction]
    public function filter()
    {
        if (null === $filter = $this->getFilter()) {
            return $this->redirectToRoute($this->routeName, $this->routeParameters);
        }

        $this->submitForm();

        $filterData = [];

        foreach (array_keys((array) $this->getForm()->getViewData()) as $attr) {
            if (!$this->getForm()->has($attr)) {
                continue;
            }

            $f = $this->getForm()->get($attr);
            $v = $f->getViewData();
            if ($f->isEmpty() && !($this->routeParameters[$attr] ?? null)) {
                continue;
            }

            $filterData[$attr] = $v;
        }

        $this->routeParameters = array_merge($this->routeParameters, $filterData);

        return $this->redirectToRoute($this->routeName, array_filter($this->routeParameters));
    }

    #[LiveAction]
    public function unsetFilter(#[LiveArg] string $filterName)
    {
        unset($this->routeParameters[$filterName]);

        return $this->redirectToRoute($this->routeName, array_filter($this->routeParameters));
    }

    #[LiveAction]
    public function paginate(#[LiveArg] int $page)
    {
        $this->routeParameters['page'] = $page;
    }

    //    #[LiveAction]
    //    public function view(#[LiveArg] Participant $participant)
    //    {
    //        $this->emit('view', [
    //            'participant' => $participant,
    //        ]);
    //    }

    protected function instantiateForm(): FormInterface
    {
        if (null === $filter = $this->getFilter()) {
            return $this->formFactory->create();
        }

        $filterDataFromUrl = array_filter($this->routeParameters, fn (string $key) => \in_array($key, $this->getFilters(), true), \ARRAY_FILTER_USE_KEY);

        $filterForm = $this->formFactory->create($filter::class);
        $filterForm->submit($filterDataFromUrl);

        return $this->formFactory->create($filter::class, $filterForm->getData());
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
        if ('' === $this->sorting) {
            $this->sorting = $this->getFilter()?->getSortable()[0] ?? '';
        }

        $this->getFilter()?->applySortingFor($this->sorting, $this->qb);
    }

    private function getFilter(): ?AbstractFilter
    {
        $entity = explode(' ', (string) $this->qb->getDQLPart('from')[0], 2)[0];

        return $this->filterRegistry->byEntity($entity);
    }
}
