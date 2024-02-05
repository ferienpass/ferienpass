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

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserLoginType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', LoginUsernameType::class)
            ->add('password', LoginPasswordType::class)
            ->add('autologin', CheckboxType::class, ['required' => false])
            ->add('_target_path', LoginTargetPathType::class, ['data' => $options['target_path']])
            ->add('submit', SubmitType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'target_path' => '',
            'mapped' => false,
            'label_format' => 'login.%name%',
            'translation_domain' => 'admin',
        ]);

        $resolver
            ->setAllowedTypes('target_path', 'string')
            ->setRequired('target_path')
        ;
    }
}
