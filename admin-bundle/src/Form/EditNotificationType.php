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

use Ferienpass\CoreBundle\Entity\Notification;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EditNotificationType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Notification::class,
            'label_format' => 'notifications.label.%name%',
            'translation_domain' => 'admin',
        ]);

        $resolver
            ->setDefined('supports_email')
            ->setDefault('supports_email', true)
            ->setDefined('supports_sms')
            ->setDefault('supports_sms', false)
        ;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['supports_email']) {
            $builder
                ->add('emailSubject', null, ['fieldset_group' => 'email', 'width' => '2/3'])
                ->add('emailText', TextareaType::class, ['attr' => ['rows' => 5], 'fieldset_group' => 'email', 'help' => 'notifications.help.emailText'])
            ;
        }

        if ($options['supports_sms']) {
            $builder
                ->add('smsText', TextareaType::class, ['attr' => ['rows' => 2], 'fieldset_group' => 'sms', 'help' => 'notifications.help.smsText'])
            ;
        }

        $builder->add('submit', SubmitType::class);
    }
}
