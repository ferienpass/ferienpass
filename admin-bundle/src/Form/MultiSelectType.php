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

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MultiSelectType extends AbstractType
{
    public const FORM_NAME = 'ms';

    public function getBlockPrefix()
    {
        return self::FORM_NAME;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label_format' => 'ms.%name%',
            'translation_domain' => 'admin',
        ]);

        $resolver
            ->setDefined('items')
            ->setAllowedTypes('items', 'array')
            ->setRequired('items')
        ;

        $resolver
            ->setDefined('buttons')
            ->setAllowedTypes('buttons', 'array')
            ->setRequired('buttons')
        ;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (\count($options['items'])) {
            $builder
                ->add('items', EntityType::class, [
                    'class' => $options['items'][0]::class,
                    'choices' => $options['items'],
                    'multiple' => true,
                    'expanded' => true,
                ]);
        }

        foreach ($options['buttons'] as $button) {
            $builder->add($button, SubmitType::class, [
                'block_prefix' => 'ms_submit',
            ]);
        }
    }
}
