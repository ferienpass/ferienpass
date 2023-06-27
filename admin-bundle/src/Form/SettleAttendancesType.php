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

use Ferienpass\AdminBundle\Form\CompoundType\PaymentItemType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SettleAttendancesType extends AbstractType
{
    public const FORM_NAME = 'settle';

    public function getBlockPrefix()
    {
        return self::FORM_NAME;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label_format' => 'payments.%name%',
            'translation_domain' => 'admin',
        ]);

        $resolver
            ->setDefined('attendances')
            ->setAllowedTypes('attendances', 'array')
            ->setRequired('attendances')
        ;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('items', CollectionType::class, [
                'entry_options' => ['label' => false],
                'entry_type' => PaymentItemType::class,
            ])
            ->add('address', TextareaType::class, [
                'attr' => ['rows' => 4],
            ])
            ->add('email', EmailType::class, [
                'required' => false,
            ])
            // Carry the selection from multi-select, so that after page-submit the collection is still available
            // If we don't add this, and do not have knowledge about the original items submitted via the multi-select, the collection types do not work since it won't recognize the entities.
            ->add('ms', CollectionType::class, [
                'data' => $options['attendances'],
                'label' => false,
                'entry_type' => HiddenType::class,
                'mapped' => false,
            ])
            ->add('submit', SubmitType::class)
        ;
    }
}
