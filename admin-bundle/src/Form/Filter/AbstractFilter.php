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

namespace Ferienpass\AdminBundle\Form\Filter;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractFilter extends AbstractType
{
    protected array $filterTypes = [];

    abstract public static function getEntity(): string;

    public function getSearchable(): array
    {
        return array_keys(static::getSorting());
    }

    public function applySortingFor(string $field, QueryBuilder $qb): void
    {
        $callable = static::getSorting()[$field] ?? null;
        if (!\is_callable($callable)) {
            return;
        }

        $callable($qb);
    }

    public function getFilterable(): array
    {
        return array_keys(static::getFilters());
    }

    public function getSortable(): array
    {
        return array_keys(static::getSorting());
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'translation_domain' => 'admin',
            'required' => false,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        foreach ($this->getFilters() as $filterName => $filterType) {
            if ($builder->has($filterName)) {
                continue;
            }

            $builder->add($filterName, $filterType::class);
        }

        $builder
            ->add('submit', SubmitType::class, [
                'label' => 'Filtern',
            ])
        ;
    }

    public function apply(QueryBuilder $qb, FormInterface $form): void
    {
        foreach ($this->getFilters() as $k => $filter) {
            $filterForm = $form->get($k);
            if (!is_a($filter, AbstractFilterType::class, true)) {
                continue;
            }

            $filter->apply($qb, $filterForm);
        }
    }

    protected function getFilters(): array
    {
        return $this->filterTypes;
    }

    abstract protected static function getSorting(): array;
}
