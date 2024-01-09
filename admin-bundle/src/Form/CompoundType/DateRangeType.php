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

namespace Ferienpass\AdminBundle\Form\CompoundType;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DateRangeType extends AbstractType
{
    public function getBlockPrefix(): string
    {
        return 'date_range';
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('begin', DateTimeType::class, [
                'label' => false,
                'html5' => false,
                'date_widget' => 'single_text',
                'date_format' => 'dd.MM.yyyy',
                'minutes' => [0, 15, 30, 45],
                'property_path' => $options['field_begin'],
            ])
            ->add('end', DateTimeType::class, [
                'label' => false,
                'html5' => false,
                'date_format' => 'dd.MM.yyyy',
                'date_widget' => 'single_text',
                'minutes' => [0, 15, 30, 45],
                'property_path' => $options['field_end'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'inherit_data' => true,
        ]);
        $resolver->setDefault('field_begin', '[begin]');
        $resolver->setDefault('field_end', '[end]');
    }
}
