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

use Contao\MemberModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EditAccountType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MemberModel::class,
            'label_format' => 'accounts.label.%name%',
            'translation_domain' => 'admin',
            'required' => false,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstname', null, ['fieldset_group' => 'base', 'width' => '1/2'])
            ->add('lastname', null, ['fieldset_group' => 'base', 'width' => '1/2'])
            ->add('phone', null, ['fieldset_group' => 'contact', 'width' => '1/2'])
            ->add('fax', null, ['fieldset_group' => 'contact', 'width' => '1/2'])
            ->add('mobile', null, ['fieldset_group' => 'contact', 'width' => '1/2'])
            ->add('email', EmailType::class, ['fieldset_group' => 'contact', 'width' => '1/2|clr'])
            ->add('street', null, ['fieldset_group' => 'address'])
            ->add('postal', null, ['fieldset_group' => 'address', 'width' => '1/3'])
            ->add('city', null, ['fieldset_group' => 'address', 'width' => '2/3'])
            ->add('submit', SubmitType::class, [
                'label' => 'Daten speichern',
            ])
        ;
    }
}
