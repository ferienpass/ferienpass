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
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NotificationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('emailSubject', TextType::class, [
                'label' => 'notifications.emailSubject',
                // 'translation_domain' => 'contao_tl_member',
                //                'constraints' => [
                //                    new NotBlank(),
                //                ],
            ])
            ->add('emailText', TextareaType::class, [
                'label' => 'notifications.emailText',
                'attr' => [
                    'rows' => 5,
                ],
                //                'translation_domain' => 'contao_tl_member',
                //                'constraints' => [
                //                    new NotBlank(),
                //                ],
            ])
            ->add('smsText', TextType::class, [
                'label' => 'notifications.smsText',
                //                'label' => 'tl_member.phone.0',
                //                'translation_domain' => 'contao_tl_member',
                //                'required' => false,
                //                'constraints' => [
                //                    new PhoneNumber(['defaultRegion' => 'DE']),
                //                ],
            ])
        ;

        $builder
            ->add('submit', SubmitType::class, [
                'label' => 'notifications.submit',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Notification::class,
        ]);
    }
}
