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

use Ferienpass\AdminBundle\Dto\HostRegistrationDto;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatableMessage;

class HostRegistrationType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => HostRegistrationDto::class,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstname', null, [
                'label' => new TranslatableMessage('tl_member.firstname.0', [], 'contao_tl_member'),
            ])
            ->add('lastname', null, [
                'label' => new TranslatableMessage('tl_member.lastname.0', [], 'contao_tl_member'),
            ])
            ->add('userPhone', null, [
                'label' => new TranslatableMessage('tl_member.phone.0', [], 'contao_tl_member'),
                'required' => false,
            ])
            ->add('userEmail', EmailType::class, [
                'label' => new TranslatableMessage('tl_member.email.0', [], 'contao_tl_member'),
            ])
            ->add('userPassword', PasswordType::class, [
                'label' => new TranslatableMessage('MSC.password.0', [], 'contao_default'),
            ])
            ->add('name', null, [
                'label' => new TranslatableMessage('Host.name.0', [], 'contao_Host'),
            ])
            ->add('street', null, [
                'label' => new TranslatableMessage('Host.street.0', [], 'contao_Host'),
                'required' => false,
            ])
            ->add('postal', null, [
                'label' => new TranslatableMessage('Host.postal.0', [], 'contao_Host'),
                'required' => false,
            ])
            ->add('city', null, [
                'label' => new TranslatableMessage('Host.city.0', [], 'contao_Host'),
                'required' => false,
            ])
            ->add('email', EmailType::class, ['label' => new TranslatableMessage('Host.email.0', [], 'contao_Host'), 'required' => false])
            ->add('phone', null, [
                'label' => new TranslatableMessage('Host.phone.0', [], 'contao_Host'),
                'required' => false,
            ])
            ->add('website', UrlType::class, [
                'label' => new TranslatableMessage('Host.website.0', [], 'contao_Host'),
                'required' => false,
            ])
            ->add('text', TextareaType::class, [
                'label' => new TranslatableMessage('Host.text.0', [], 'contao_Host'),
                'required' => false,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Registrierung absenden',
            ])
        ;
    }
}
