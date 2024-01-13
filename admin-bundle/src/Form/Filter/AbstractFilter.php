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
    abstract public static function getEntity(): string;

    public function getSearchable(): array
    {
        return array_keys(static::getSorting());
    }

    public function getOrderByFor(string $field): string|array|null
    {
        return static::getSorting()[$field] ?? null;
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
        foreach (static::getFilters() as $k => $v) {
            if ($builder->has($k)) {
                continue;
            }

            $builder->add($k, $v);
        }

        $builder
            ->add('submit', SubmitType::class, [
                'label' => 'Filtern',
            ])
        ;
    }

    public function apply(QueryBuilder $qb, FormInterface $form): void
    {
        foreach (static::getFilters() as $k => $v) {
            $f = $form->get($k);
            if ($f->isEmpty()) {
                continue;
            }

            $filter = static::getFilters()[$k];
            if (!is_a($filter, AbstractFilterType::class, true)) {
                continue;
            }

            $filter::apply($qb, $f);

            // $this->filtersViewData[$name] = $this->getFilterType($name)?->getViewData($form);
        }
    }

    abstract protected static function getFilters(): array;

    abstract protected static function getSorting(): array;
}
