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

namespace Ferienpass\AdminBundle\Form;

use Ferienpass\AdminBundle\Form\CompoundType\OfferCategoriesType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EditOfferCategoriesType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // 'data_class' => Edition::class,
            'label_format' => 'offerCategories.label.%name%',
            'translation_domain' => 'admin',
            'required' => false,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('categories', OfferCategoriesType::class, ['fieldset_group' => 'categories', 'label' => false])
            ->add('submit', SubmitType::class, [
                'label' => 'Daten speichern',
            ])
        ;
    }
}
