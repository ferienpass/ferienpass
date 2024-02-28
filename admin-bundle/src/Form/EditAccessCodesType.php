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

use Ferienpass\AdminBundle\Form\CompoundType\AccessCodesType;
use Ferienpass\CoreBundle\Entity\AccessCodeStrategy;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EditAccessCodesType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AccessCodeStrategy::class,
            'label_format' => 'accessCodes.label.%name%',
            'translation_domain' => 'admin',
            'required' => true,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', null, ['fieldset_group' => 'base', 'width' => '2/3'])
            ->add('max', IntegerType::class, ['fieldset_group' => 'settings', 'help' => 'accessCodes.help.max'])
            ->add('codes', AccessCodesType::class, ['fieldset_group' => 'codes', 'label' => false])
            ->add('submit', SubmitType::class, [
                'label' => 'Daten speichern',
            ])
        ;
    }
}
