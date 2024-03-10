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

namespace Ferienpass\CmsBundle\Form;

use Ferienpass\CoreBundle\Filter\Type\FilterType;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ListFiltersType extends AbstractType
{
    /** @var array<FilterType> */
    private array $filterTypes = [];

    public function __construct(#[TaggedIterator('ferienpass.filter.offer_list_type', defaultIndexMethod: 'getName')] iterable $filterTypes)
    {
        $this->filterTypes = $filterTypes instanceof \Traversable ? iterator_to_array($filterTypes, true) : $this->filterTypes;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        foreach ($this->filterTypes as $filterType) {
            $builder->add($filterType::getName(), $filterType::class);
        }

        if (!($options['short'] ?? false)) {
            $builder->add('submit', SubmitType::class, ['label' => 'Filter anwenden']);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefined('short');
        $resolver->setAllowedTypes('short', 'bool');
    }
}
