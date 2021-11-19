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

namespace Ferienpass\CoreBundle\Form\Filter;

use Contao\CoreBundle\Exception\RedirectResponseException;
use Doctrine\ORM\QueryBuilder;
use Ferienpass\CoreBundle\Form\OfferFiltersType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class OfferFilters
{
    private FormFactoryInterface $formFactory;
    private UrlGeneratorInterface $urlGenerator;

    /** @var array<string, FilterTypeInterface> */
    private array $filters;
    private FormInterface $form;

    public function __construct(FormFactoryInterface $formFactory, UrlGeneratorInterface $urlGenerator, \Traversable $filters)
    {
        $this->formFactory = $formFactory;
        $this->urlGenerator = $urlGenerator;
        $this->filters = iterator_to_array($filters);
    }

    public function createForm(): self
    {
        $this->form = $this->formFactory->create(OfferFiltersType::class);

        foreach ($this->filters as $name => $service) {
            $this->form->add($name, \get_class($service));
        }

        return $this;
    }

    public function createView(): FormView
    {
        return $this->form->createView();
    }

    public function handleRequest(Request $request, QueryBuilder $qb)
    {
        // Remove filter from URL
        if ($request->isMethod('delete') && $request->request->has('filter')) {
            throw new RedirectResponseException($this->urlGenerator->generate($request->get('_route'), array_filter($request->query->all(), fn (string $k) => $k !== $request->request->get('filter'), \ARRAY_FILTER_USE_KEY)));
        }

        $form = $this->form;

        // When filter form is submitted, add the used filters to the URL
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $submitted = $form->getData();
            $submitted = array_filter($submitted);

            throw new RedirectResponseException($this->urlGenerator->generate($request->get('_route'), $submitted + $request->query->all()));
        }

        // Read the filters from the URL
        $request2 = clone $request;
        $request2->setMethod('POST');
        $request2->request->add($request->query->all());
        $form->handleRequest($request2);

        // Apply filters
        foreach ($form->getData() ?? [] as $filterName => $filterValue) {
            if ($form->has($filterName) && $form->get($filterName)->getData()) {
                $widgetForm = $form->get($filterName);
                $filter = $this->filters[$filterName] ?? null;

                if (!$filter instanceof FilterTypeInterface) {
                    continue;
                }

                // Let the filter form type modify the query
                $filter->modifyQuery($qb, $widgetForm);
            }
        }
    }
}
